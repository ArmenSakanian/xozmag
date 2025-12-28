<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/vitrina/evotor_catalog_core.php";

// Можно прокинуть nocache=1 в sync, если хочешь
$nocache = isset($_GET["nocache"]) && $_GET["nocache"] === "1";

$result = evotor_catalog_build([
  "nocache" => $nocache,
  "debug" => false,
  "cache_ttl" => 1,
]);

if (empty($result["ok"])) {
  echo json_encode([
    "success" => false,
    "error" => "Не удалось загрузить evotor_catalog",
    "details" => $result["error"] ?? null
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = $result["payload"];
if (!is_array($data) || empty($data["products"]) || !is_array($data["products"])) {
  echo json_encode(["success" => false, "error" => "Неверный формат данных"], JSON_UNESCAPED_UNICODE);
  exit;
}

$products = $data["products"];

// дальше оставляешь твою логику INSERT/UPDATE/DELETE как есть


$inserted = 0;
$updated  = 0;
$deleted  = 0;
$unchanged = 0;

$apiBarcodes = [];

$LIMIT = 200;
$truncated = false;

$insertedItems = [];
$updatedItems  = [];
$deletedItems  = [];

function norm_str($v) {
  $v = (string)($v ?? "");
  $v = trim($v);
  $v = preg_replace('/\s+/u', ' ', $v);
  return $v;
}

function norm_num($v) {
  if ($v === null || $v === "") return 0;
  return (float)$v;
}

function safe_rel_from_url_or_path($p) {
  $p = (string)$p;
  $p = trim($p);
  if ($p === "") return "";

  // URL -> path
  if (preg_match('~^https?://~i', $p)) {
    $u = parse_url($p);
    $p = !empty($u["path"]) ? $u["path"] : "";
  }

  // убрать query/fragment если вдруг прилетело без parse_url
  $p = preg_replace('~[?#].*$~', '', $p);

  $p = trim((string)$p);
  return $p;
}

/**
 * Нормализация путей строго под /photo_product_vitrina/
 * - приводим URL/путь к path
 * - гарантируем ведущий "/"
 * - принимаем только то, что лежит в /photo_product_vitrina/
 * - уникализация + сортировка
 */
function normalize_images_vitrina_strict($v) {
  $arr = [];

  if (is_array($v)) {
    $arr = $v;
  } else {
    $s = trim((string)$v);
    if ($s !== "") {
      $decoded = json_decode($s, true);
      if (is_array($decoded)) $arr = $decoded;
    }
  }

  $out = [];
  foreach ($arr as $p) {
    $p = safe_rel_from_url_or_path($p);
    $p = trim((string)$p);
    if ($p === "") continue;

    // ведущий слэш
    if ($p !== "" && $p[0] !== "/") $p = "/" . $p;

    // строго только витрина
    if (strpos($p, "/photo_product_vitrina/") !== 0) continue;

    $out[] = $p;
  }

  $out = array_values(array_unique($out));
  sort($out);
  return $out;
}

function safe_delete_file_in_vitrina($relPath, &$deletedList, &$missingList) {
  $relPath = safe_rel_from_url_or_path($relPath);

  if ($relPath === "") return;
  if ($relPath[0] !== "/") $relPath = "/" . $relPath;

  if (strpos($relPath, "/photo_product_vitrina/") !== 0) return;

  $folderAbs = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . "/photo_product_vitrina/";
  $abs = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . $relPath;

  $realFolder = realpath($folderAbs);
  $realFile   = realpath($abs);

  if ($realFolder && $realFile) {
    if (strpos($realFile, $realFolder) !== 0) return;
  }

  if (file_exists($abs)) {
    if (@unlink($abs)) $deletedList[] = $relPath;
    else $missingList[] = $relPath . " (no_permission)";
  } else {
    $missingList[] = $relPath;
  }
}

function collect_photos_for_barcode($barcode, $photoColumn) {
  $barcode = trim((string)$barcode);
  if ($barcode === "") return [];

  // 1) если в БД в photo уже есть JSON массив путей — используем его (строго витрина)
  $paths = normalize_images_vitrina_strict($photoColumn);
  if (!empty($paths)) return $paths;

  // 2) иначе ищем по имени файла:
  $baseRel = "/photo_product_vitrina/" . $barcode;
  $baseAbs = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . $baseRel;

  $first = $baseAbs . ".webp";
  if (file_exists($first)) {
    $found = [$baseRel . ".webp"];
    for ($i = 1; $i < 100; $i++) {
      $pAbs = $baseAbs . "_" . $i . ".webp";
      if (file_exists($pAbs)) $found[] = $baseRel . "_" . $i . ".webp";
      else break;
    }
    return $found;
  }

  $p1 = $baseAbs . "_1.webp";
  if (file_exists($p1)) {
    $found = [$baseRel . "_1.webp"];
    for ($i = 2; $i < 100; $i++) {
      $pAbs = $baseAbs . "_" . $i . ".webp";
      if (file_exists($pAbs)) $found[] = $baseRel . "_" . $i . ".webp";
      else break;
    }
    return $found;
  }

  return [];
}

$sqlSelect = $pdo->prepare("
  SELECT id, name, article, brand, type, price, quantity, description, photo
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

  $barcode = trim((string)($p["barcode"] ?? ""));
  if ($barcode === "") continue;

  $apiBarcodes[] = $barcode;

  $name        = (string)($p["name"] ?? "");
  $article     = (string)($p["article"] ?? "");
  $brand       = (string)($p["brandName"] ?? "");
  $type        = (string)($p["typeName"] ?? "");
  $price       = $p["price"] ?? 0;
  $quantity    = $p["quantity"] ?? 0;
  $description = (string)($p["description"] ?? "");

  // ✅ строго: записываем в БД ровно то, что дал Evotor (после нормализации путей витрины)
  $imagesArr  = normalize_images_vitrina_strict($p["images"] ?? []);
  $imagesJson = json_encode($imagesArr, JSON_UNESCAPED_UNICODE);

  $sqlSelect->execute([$barcode]);
  $row = $sqlSelect->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    $changed = [];

    if (norm_str($row["name"] ?? "")        !== norm_str($name))        $changed[] = "name";
    if (norm_str($row["article"] ?? "")     !== norm_str($article))     $changed[] = "article";
    if (norm_str($row["brand"] ?? "")       !== norm_str($brand))       $changed[] = "brand";
    if (norm_str($row["type"] ?? "")        !== norm_str($type))        $changed[] = "type";
    if (norm_num($row["price"] ?? 0)        !== norm_num($price))       $changed[] = "price";
    if (norm_num($row["quantity"] ?? 0)     !== norm_num($quantity))    $changed[] = "quantity";
    if (norm_str($row["description"] ?? "") !== norm_str($description)) $changed[] = "description";

    // ✅ сравниваем фото строго по нормализованным путям витрины
    $dbImagesArr = normalize_images_vitrina_strict($row["photo"] ?? "");
    if ($dbImagesArr !== $imagesArr) $changed[] = "photo";

    if (!empty($changed)) {
      $sqlUpdate->execute([
        $name, $article, $brand, $type, $price, $description, $imagesJson, $quantity, $barcode
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
      $name, $article, $brand, $type, $price, $barcode, $description, $imagesJson, $quantity
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
   2) DELETE товаров, которых нет в API
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

  $stmtToDelete = $pdo->query("
    SELECT p.id, p.barcode, p.name, p.photo
    FROM products p
    LEFT JOIN tmp_api_barcodes t ON t.barcode = p.barcode
    WHERE t.barcode IS NULL
      AND p.barcode IS NOT NULL
      AND p.barcode <> ''
  ");

  $rowsToDelete = $stmtToDelete ? $stmtToDelete->fetchAll(PDO::FETCH_ASSOC) : [];

  foreach ($rowsToDelete as $r) {
    $bc = trim((string)($r["barcode"] ?? ""));
    if ($bc === "") continue;

    $delPhotos = [];
    $missPhotos = [];

    $paths = collect_photos_for_barcode($bc, $r["photo"] ?? "");
    foreach ($paths as $rel) {
      safe_delete_file_in_vitrina($rel, $delPhotos, $missPhotos);
    }

    if (empty($paths)) $missPhotos[] = "(no_photos_found)";

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
