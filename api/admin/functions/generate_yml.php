<?php
// /api/yml/generate_yml.php
// Генерация YML-фида в /yml.xml (и /yml.xml.gz)
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";

header("Content-Type: text/plain; charset=utf-8");

// ====== НАСТРОЙКИ ======
$SHOP_NAME    = "Xozmag";
$SHOP_COMPANY = "Xozmag";
$BASE_URL     = "https://xozmag.ru";
$CURRENCY_ID  = "RUB";

$OUT_XML = $_SERVER['DOCUMENT_ROOT'] . "/yml.xml";
$OUT_GZ  = $_SERVER['DOCUMENT_ROOT'] . "/yml.xml.gz";

$SECRET_TOKEN = ""; // например "mySecret123". Пусто = без защиты

// ====== ПРОВЕРКА ТОКЕНА ======
if ($SECRET_TOKEN !== "") {
  $token = $_GET['token'] ?? '';
  if (!hash_equals($SECRET_TOKEN, (string)$token)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
  }
}

// ====== БД ======
require_once __DIR__ . "/../../db.php";
if (!isset($pdo) || !($pdo instanceof PDO)) {
  http_response_code(500);
  echo "DB error: \$pdo (PDO) not found in db.php\n";
  exit;
}
$pdo->exec("SET NAMES utf8mb4");

// ====== LOCK ======
$lockFile = sys_get_temp_dir() . "/yml_generate.lock";
$lockFp = fopen($lockFile, "c+");
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
  http_response_code(429);
  echo "Another generation is running\n";
  exit;
}

try {
  // ====== КАТЕГОРИИ ======
  $cats = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY id ASC")
              ->fetchAll(PDO::FETCH_ASSOC);

  // ====== ТОВАРЫ: только с фото, ценой, категорией, остатком >=1, и slug (чтобы URL был /product/slug) ======
  $sql = "
    SELECT
      id, slug, name, article, brand, type, price, quantity, barcode, description, photo, category_id
    FROM products
    WHERE
      slug IS NOT NULL
      AND slug <> ''
      AND photo IS NOT NULL
      AND photo <> ''
      AND photo <> '[]'
      AND photo <> 'null'
      AND name IS NOT NULL
      AND name <> ''
      AND price IS NOT NULL
      AND price > 0
      AND category_id IS NOT NULL
      AND quantity IS NOT NULL
      AND quantity >= 1
  ";
  $products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  // ====== XML ======
  $doc = new DOMDocument("1.0", "UTF-8");
  $doc->formatOutput = true;

  $yml = $doc->createElement("yml_catalog");
  $yml->setAttribute("date", date("Y-m-d H:i"));
  $doc->appendChild($yml);

  $shop = $doc->createElement("shop");
  $yml->appendChild($shop);

  $shop->appendChild($doc->createElement("name", $SHOP_NAME));
  $shop->appendChild($doc->createElement("company", $SHOP_COMPANY));
  $shop->appendChild($doc->createElement("url", $BASE_URL));

  // currencies
  $currencies = $doc->createElement("currencies");
  $cur = $doc->createElement("currency");
  $cur->setAttribute("id", $CURRENCY_ID);
  $cur->setAttribute("rate", "1");
  $currencies->appendChild($cur);
  $shop->appendChild($currencies);

  // categories
  $categoriesEl = $doc->createElement("categories");
  foreach ($cats as $c) {
    $catEl = $doc->createElement("category");
    $catEl->setAttribute("id", (string)$c["id"]);
    if (!empty($c["parent_id"])) {
      $catEl->setAttribute("parentId", (string)$c["parent_id"]);
    }
    $catEl->appendChild($doc->createTextNode((string)$c["name"]));
    $categoriesEl->appendChild($catEl);
  }
  $shop->appendChild($categoriesEl);

  // offers
  $offersEl = $doc->createElement("offers");

  $added = 0;
  foreach ($products as $p) {
    $pictures = parsePhotos($p["photo"] ?? "");
    if (!$pictures) continue; // железно пропускаем без фото

    $qty = (int)($p["quantity"] ?? 0);
    if ($qty < 1) continue; // страховка (даже если SQL уже фильтрует)

    $slug = trim((string)($p["slug"] ?? ""));
    if ($slug === "") continue; // URL у нас строго /product/slug

    $offer = $doc->createElement("offer");
    $offer->setAttribute("id", (string)$p["id"]);
    $offer->setAttribute("available", "true"); // раз qty >= 1

    // url товара - по slug
    $url = rtrim($BASE_URL, "/") . "/product/" . rawurlencode($slug);
    $offer->appendChild($doc->createElement("url", $url));

    // price / currency / category
    $offer->appendChild($doc->createElement("price", formatPrice($p["price"])));
    $offer->appendChild($doc->createElement("currencyId", $CURRENCY_ID));
    $offer->appendChild($doc->createElement("categoryId", (string)$p["category_id"]));

    // picture (берём первое)
    $firstPic = $pictures[0] ?? "";
    if ($firstPic !== "") {
      $picUrl = makeAbsolute($BASE_URL, $firstPic);
      $offer->appendChild($doc->createElement("picture", $picUrl));
    }

    // name
    $offer->appendChild($doc->createElement("name", trim((string)$p["name"])));

    // vendor / vendorCode
    if (!empty($p["brand"])) {
      $offer->appendChild($doc->createElement("vendor", trim((string)$p["brand"])));
    }
    if (!empty($p["article"])) {
      $offer->appendChild($doc->createElement("vendorCode", trim((string)$p["article"])));
    }

    // barcode
    if (!empty($p["barcode"])) {
      $offer->appendChild($doc->createElement("barcode", trim((string)$p["barcode"])));
    }

    // description (CDATA)
    if (!empty($p["description"])) {
      $descEl = $doc->createElement("description");
      $descEl->appendChild($doc->createCDATASection(cleanDesc((string)$p["description"])));
      $offer->appendChild($descEl);
    }

    $offersEl->appendChild($offer);
    $added++;
  }

  $shop->appendChild($offersEl);

  // ====== SAVE ATOMIC ======
  $xml = $doc->saveXML();

  $tmp = $OUT_XML . ".tmp";
  file_put_contents($tmp, $xml);
  rename($tmp, $OUT_XML);

  $gz = gzencode($xml, 9);
  file_put_contents($OUT_GZ . ".tmp", $gz);
  rename($OUT_GZ . ".tmp", $OUT_GZ);

  echo "OK. categories=" . count($cats) . " offers=" . $added . "\n";
  echo "Saved: $OUT_XML\n";
  echo "Saved: $OUT_GZ\n";

} catch (Throwable $e) {
  http_response_code(500);
  echo "ERROR: " . $e->getMessage() . "\n";
} finally {
  if ($lockFp) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
  }
}

// ====== HELPERS ======

function safe_rel_from_url_or_path($p): string {
  $p = trim((string)$p);
  if ($p === "") return "";
  $u = parse_url($p);
  $path = $u["path"] ?? $p;
  $path = trim((string)$path);
  if ($path === "") return "";
  if ($path[0] !== "/") $path = "/" . ltrim($path, "/");
  return $path;
}

function parsePhotos($photoField): array {
  if ($photoField === null) return [];

  $s = trim((string)$photoField);
  if ($s === "" || $s === "[]" || $s === "null") return [];

  $arr = json_decode($s, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
    $out = [];
    foreach ($arr as $v) {
      $v = safe_rel_from_url_or_path($v);
      if ($v !== "" && $v !== "/null") $out[] = $v;
    }
    return array_values(array_unique($out));
  }

  // не JSON - одна строка
  $one = safe_rel_from_url_or_path($s);
  return $one ? [$one] : [];
}

function makeAbsolute(string $base, string $path): string {
  $path = trim((string)$path);
  if ($path === "") return rtrim($base, "/") . "/";
  if (preg_match('~^https?://~i', $path)) return $path;
  if ($path[0] !== "/") $path = "/" . $path;
  return rtrim($base, "/") . $path;
}

function formatPrice($price): string {
  return number_format((float)$price, 2, ".", "");
}

function cleanDesc(string $s): string {
  return trim($s);
}