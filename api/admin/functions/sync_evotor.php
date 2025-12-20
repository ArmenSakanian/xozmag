<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/*
  Логика:
  - inserted: товара не было в БД, но пришёл из API
  - updated: товар был в БД, и изменилось хотя бы одно из: name, article, price, quantity
  - deleted: товар был в БД, но его barcode НЕ пришёл из API => удаляем из БД + удаляем фото из /photo_product_vitrina
*/

$url = "https://xozmag.ru/api/vitrina/evotor_catalog.php";
$json = @file_get_contents($url);

if (!$json) {
  echo json_encode(["success" => false, "error" => "Не удалось загрузить evotor_catalog"], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode($json, true);
if (!is_array($data) || empty($data["products"]) || !is_array($data["products"])) {
  echo json_encode(["success" => false, "error" => "Неверный формат данных"], JSON_UNESCAPED_UNICODE);
  exit;
}

$products = $data["products"];

$inserted = 0;
$updated  = 0;
$deleted  = 0;
$unchanged = 0;

$apiBarcodes = [];

// Детализация для Vue (ограничим, чтобы не улететь в мегабайты)
$LIMIT = 200;
$truncated = false;

$insertedItems = [];
$updatedItems  = [];
$deletedItems  = [];

function norm_str($v) {
  $v = (string)($v ?? "");
  $v = trim($v);
  // нормализуем множественные пробелы
  $v = preg_replace('/\s+/u', ' ', $v);
  return $v;
}

function norm_num($v) {
  if ($v === null || $v === "") return 0;
  // у тебя price обычно int, quantity тоже, но на всякий:
  return (float)$v;
}

function safe_rel_from_url_or_path($p) {
  $p = (string)$p;
  $p = trim($p);
  if ($p === "") return "";
  // если вдруг прилетит полный URL — берём только path
  if (preg_match('~^https?://~i', $p)) {
    $u = parse_url($p);
    if (!empty($u["path"])) return $u["path"];
    return "";
  }
  return $p;
}

function safe_delete_file_in_vitrina($relPath, &$deletedList, &$missingList) {
  $relPath = safe_rel_from_url_or_path($relPath);

  if ($relPath === "") return;
  // разрешаем удалять ТОЛЬКО из этой папки
  if (strpos($relPath, "/photo_product_vitrina/") !== 0) {
    // чужие пути не трогаем
    return;
  }

  $folderAbs = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . "/photo_product_vitrina/";
  $abs = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . $relPath;

  // защита от ../
  $realFolder = realpath($folderAbs);
  $realFile   = realpath($abs);

  if ($realFolder && $realFile) {
    if (strpos($realFile, $realFolder) !== 0) {
      return;
    }
  }

  if (file_exists($abs)) {
    if (@unlink($abs)) {
      $deletedList[] = $relPath;
    } else {
      // файл был, но не удалился (права)
      $missingList[] = $relPath . " (no_permission)";
    }
  } else {
    $missingList[] = $relPath;
  }
}

function collect_photos_for_barcode($barcode, $photoColumn) {
  $barcode = trim((string)$barcode);
  if ($barcode === "") return [];

  $paths = [];

  // 1) если в БД в photo уже есть JSON массив путей — используем его
  $photoColumn = trim((string)$photoColumn);
  if ($photoColumn !== "") {
    $decoded = json_decode($photoColumn, true);
    if (is_array($decoded)) {
      foreach ($decoded as $p) {
        $p = safe_rel_from_url_or_path($p);
        if ($p && strpos($p, "/photo_product_vitrina/") === 0) {
          $paths[] = $p;
        }
      }
      if (!empty($paths)) {
        // уникальные и стабильный порядок
        $paths = array_values(array_unique($paths));
        sort($paths);
        return $paths;
      }
    }
  }

  // 2) иначе ищем по имени файла:
  // barcode.webp, затем barcode_1.webp, barcode_2.webp... пока следующий не найден
  $baseRel = "/photo_product_vitrina/" . $barcode;
  $baseAbs = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . $baseRel;

  $first = $baseAbs . ".webp";
  if (file_exists($first)) {
    $paths[] = $baseRel . ".webp";
    for ($i = 1; $i < 100; $i++) {
      $pAbs = $baseAbs . "_" . $i . ".webp";
      if (file_exists($pAbs)) {
        $paths[] = $baseRel . "_" . $i . ".webp";
      } else {
        break; // как ты просил: нет _1 => не идём дальше
      }
    }
    return $paths;
  }

  // если barcode.webp нет — попробуем с _1 (на всякий случай)
  $p1 = $baseAbs . "_1.webp";
  if (file_exists($p1)) {
    $paths[] = $baseRel . "_1.webp";
    for ($i = 2; $i < 100; $i++) {
      $pAbs = $baseAbs . "_" . $i . ".webp";
      if (file_exists($pAbs)) {
        $paths[] = $baseRel . "_" . $i . ".webp";
      } else {
        break;
      }
    }
  }

  return $paths;
}

/* =========================
   SQL prepared
========================= */
$sqlSelect = $pdo->prepare("
  SELECT id, name, article, price, quantity, photo
  FROM products
  WHERE barcode = ?
  LIMIT 1
");

$sqlInsert = $pdo->prepare("
  INSERT INTO products
    (name, article, brand, type, price, barcode, description, photo, quantity, category_id)
  VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
");

$sqlUpdate = $pdo->prepare("
  UPDATE products SET
    name = ?,
    article = ?,
    brand = ?,
    type = ?,
    price = ?,
    description = ?,
    photo = ?,
    quantity = ?
  WHERE barcode = ?
");

/* =========================
   1) INSERT/UPDATE loop
========================= */
foreach ($products as $p) {

  $barcode = $p["barcode"] ?? "";
  $barcode = trim((string)$barcode);
  if ($barcode === "") continue;

  $apiBarcodes[] = $barcode;

  $name        = $p["name"] ?? "";
  $article     = $p["article"] ?? "";
  $brand       = $p["brandName"] ?? "";
  $type        = $p["typeName"] ?? null;
  $price       = $p["price"] ?? 0;
  $quantity    = $p["quantity"] ?? 0;
  $description = $p["description"] ?? "";
  $images      = json_encode($p["images"] ?? [], JSON_UNESCAPED_UNICODE);

  $sqlSelect->execute([$barcode]);
  $row = $sqlSelect->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    // считаем updated ТОЛЬКО если изменилось: name/article/price/quantity
    $changed = [];

    if (norm_str($row["name"] ?? "") !== norm_str($name)) $changed[] = "name";
    if (norm_str($row["article"] ?? "") !== norm_str($article)) $changed[] = "article";
    if (norm_num($row["price"] ?? 0) !== norm_num($price)) $changed[] = "price";
    if (norm_num($row["quantity"] ?? 0) !== norm_num($quantity)) $changed[] = "quantity";

    if (!empty($changed)) {
      $sqlUpdate->execute([
        $name, $article, $brand, $type, $price, $description, $images, $quantity, $barcode
      ]);
      $updated++;

      if (count($updatedItems) < $LIMIT) {
        $updatedItems[] = [
          "barcode" => $barcode,
          "name"    => $name,
          "fields"  => $changed
        ];
      } else {
        $truncated = true;
      }
    } else {
      $unchanged++;
    }

  } else {
    $sqlInsert->execute([
      $name, $article, $brand, $type, $price, $barcode, $description, $images, $quantity
    ]);
    $inserted++;

    if (count($insertedItems) < $LIMIT) {
      $insertedItems[] = [
        "barcode" => $barcode,
        "name"    => $name
      ];
    } else {
      $truncated = true;
    }
  }
}

/* =========================
   2) DELETE: not in API
   + delete photos from /photo_product_vitrina
========================= */
if (!empty($apiBarcodes)) {

  $pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_api_barcodes");
  $pdo->exec("CREATE TEMPORARY TABLE tmp_api_barcodes (
      barcode VARCHAR(64) PRIMARY KEY
    ) ENGINE=MEMORY"
  );

  $tmpIns = $pdo->prepare("INSERT IGNORE INTO tmp_api_barcodes (barcode) VALUES (?)");
  foreach ($apiBarcodes as $b) {
    $tmpIns->execute([$b]);
  }

  // СНАЧАЛА соберём список того, что будем удалять (для фото + отчёта)
  $stmtToDelete = $pdo->query("
    SELECT p.id, p.barcode, p.name, p.photo
    FROM products p
    LEFT JOIN tmp_api_barcodes t ON t.barcode = p.barcode
    WHERE t.barcode IS NULL
      AND p.barcode IS NOT NULL
      AND p.barcode <> ''
  ");

  $rowsToDelete = $stmtToDelete ? $stmtToDelete->fetchAll(PDO::FETCH_ASSOC) : [];

  // Удаляем фото по каждому удаляемому товару
  foreach ($rowsToDelete as $r) {
    $bc = trim((string)($r["barcode"] ?? ""));
    if ($bc === "") continue;

    $delPhotos = [];
    $missPhotos = [];

    $paths = collect_photos_for_barcode($bc, $r["photo"] ?? "");
    foreach ($paths as $rel) {
      safe_delete_file_in_vitrina($rel, $delPhotos, $missPhotos);
    }

    // если вообще ничего не нашли по путям — сообщим "не найдено"
    if (empty($paths)) {
      $missPhotos[] = "(no_photos_found)";
    }

    if (count($deletedItems) < $LIMIT) {
      $deletedItems[] = [
        "barcode" => $bc,
        "name"    => (string)($r["name"] ?? ""),
        "photos_deleted_count" => count($delPhotos),
        "photos_missing_count" => count($missPhotos),
        "photos_deleted" => array_slice($delPhotos, 0, 10),
        "photos_missing" => array_slice($missPhotos, 0, 10),
      ];
    } else {
      $truncated = true;
    }
  }

  // Теперь удаляем из БД
  $deleted = (int)$pdo->exec("
    DELETE p
    FROM products p
    LEFT JOIN tmp_api_barcodes t ON t.barcode = p.barcode
    WHERE t.barcode IS NULL
      AND p.barcode IS NOT NULL
      AND p.barcode <> ''
  ");
}

echo json_encode([
  "success"   => true,
  "inserted"  => (int)$inserted,
  "updated"   => (int)$updated,
  "deleted"   => (int)$deleted,
  "unchanged" => (int)$unchanged,

  "insertedItems" => $insertedItems,
  "updatedItems"  => $updatedItems,
  "deletedItems"  => $deletedItems,
  "truncated"     => (bool)$truncated
], JSON_UNESCAPED_UNICODE);
