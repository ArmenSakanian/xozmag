<?php
// api/generate_sitemap.php
// Результат: ../sitemap-data.xml и ../sitemap-data.xml.gz
// Теперь:
// - категории -> /catalog?cat=<slug> (если slug пустой -> code -> fallback id)
// - товары    -> /product/<slug>     (если slug пустой -> id)

declare(strict_types=1);

$BASE = "https://xozmag.ru";

// ВАЖНО: не перетираем /sitemap.xml (он будет sitemap-index)
$outPath   = __DIR__ . "/../sitemap-data.xml";
$outGzPath = __DIR__ . "/../sitemap-data.xml.gz";

if (php_sapi_name() !== "cli") {
  header("Content-Type: text/plain; charset=UTF-8");
}

require_once __DIR__ . "/db.php"; // должен создать $pdo (PDO)

if (!isset($pdo) || !($pdo instanceof PDO)) {
  echo "ERROR: \$pdo не найден. Проверь api/db.php\n";
  exit(1);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ================= helpers ================= */

function xmlEscape(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, "UTF-8");
}

function atomicWrite(string $path, string $data): void {
  $tmp = $path . ".tmp";
  file_put_contents($tmp, $data);
  rename($tmp, $path);
}

function tableColumns(PDO $pdo, string $table): array {
  $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace("`", "``", $table) . "`");
  $cols = [];
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $cols[] = strtolower((string)$row["Field"]);
  }
  return $cols;
}

function findBestTables(PDO $pdo): array {
  $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);

  $bestProduct = ["name" => null, "score" => -1];
  $bestCat = ["name" => null, "score" => -1];

  foreach ($tables as $trow) {
    $t = (string)$trow[0];
    $cols = tableColumns($pdo, $t);

    // product table score
    $scoreP = 0;
    if (in_array("id", $cols, true)) $scoreP += 5;
    if (in_array("slug", $cols, true)) $scoreP += 2;
    foreach (["price","cost","sku","article","barcode","brand","title","name","category_id","cat_id"] as $c) {
      if (in_array($c, $cols, true)) $scoreP += 2;
    }
    if ($scoreP > $bestProduct["score"]) $bestProduct = ["name" => $t, "score" => $scoreP];

    // category table score
    $scoreC = 0;
    if (in_array("id", $cols, true)) $scoreC += 4;
    if (in_array("name", $cols, true) || in_array("title", $cols, true)) $scoreC += 4;
    if (in_array("slug", $cols, true)) $scoreC += 2;
    if (in_array("code", $cols, true)) $scoreC += 2;

    foreach (["parent_id","parent","parentid","cat_parent","level"] as $c) {
      if (in_array($c, $cols, true)) $scoreC += 2;
    }
    if (in_array("price", $cols, true) || in_array("sku", $cols, true)) $scoreC -= 3;

    if ($scoreC > $bestCat["score"]) $bestCat = ["name" => $t, "score" => $scoreC];
  }

  return [$bestProduct["name"], $bestCat["name"]];
}

function fetchIds(PDO $pdo, string $table, string $idCol = "id"): array {
  $sql = "SELECT `$idCol` AS id FROM `" . str_replace("`", "``", $table) . "` WHERE `$idCol` IS NOT NULL";
  $stmt = $pdo->query($sql);

  $ids = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = (string)$row["id"];
    if ($id !== "" && ctype_digit($id)) $ids[] = $id;
  }
  return $ids;
}

/**
 * Категории: берём slug, если нет — code, если нет — id.
 * Возвращает массив "ключей", которые подставляются в URL (?cat=KEY)
 */
function fetchCategoryKeys(PDO $pdo, string $table): array {
  $cols = tableColumns($pdo, $table);

  // идеальный вариант: slug + code
  if (in_array("slug", $cols, true) && in_array("code", $cols, true)) {
    $stmt = $pdo->query("SELECT slug, code FROM `" . str_replace("`","``",$table) . "` ORDER BY code ASC");
    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $slug = trim((string)($row["slug"] ?? ""));
      $code = trim((string)($row["code"] ?? ""));
      $key  = ($slug !== "") ? $slug : $code;
      if ($key !== "") $out[] = $key;
    }
    return array_values(array_unique($out));
  }

  // если slug нет, но есть code — используем code (лучше чем id)
  if (in_array("code", $cols, true)) {
    $stmt = $pdo->query("SELECT code FROM `" . str_replace("`","``",$table) . "` ORDER BY code ASC");
    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $code = trim((string)($row["code"] ?? ""));
      if ($code !== "") $out[] = $code;
    }
    return array_values(array_unique($out));
  }

  // крайний fallback: id
  return fetchIds($pdo, $table, "id");
}

/**
 * Товары: берём slug, если пусто — id.
 * Возвращает массив "ключей", которые подставляются в URL (/product/KEY)
 */
function fetchProductKeys(PDO $pdo, string $table): array {
  $cols = tableColumns($pdo, $table);

  if (in_array("slug", $cols, true)) {
    $stmt = $pdo->query("SELECT id, slug FROM `" . str_replace("`","``",$table) . "` WHERE id IS NOT NULL");
    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $id   = trim((string)($row["id"] ?? ""));
      $slug = trim((string)($row["slug"] ?? ""));
      $key  = ($slug !== "") ? $slug : $id;
      if ($key !== "") $out[] = $key;
    }
    return array_values(array_unique($out));
  }

  // если slug колонки нет — старый режим
  return fetchIds($pdo, $table, "id");
}

/* ================= main ================= */

[$productTable, $catTable] = findBestTables($pdo);

if (!$productTable) { echo "ERROR: не нашёл таблицу товаров\n"; exit(1); }
if (!$catTable) { echo "ERROR: не нашёл таблицу категорий\n"; exit(1); }

echo "Product table: {$productTable}\n";
echo "Category table: {$catTable}\n";

$productKeys = fetchProductKeys($pdo, $productTable);
$catKeys     = fetchCategoryKeys($pdo, $catTable);

echo "Products: " . count($productKeys) . "\n";
echo "Categories: " . count($catKeys) . "\n";

$urls = [];
$urls[] = $BASE . "/";
$urls[] = $BASE . "/catalog";

foreach ($catKeys as $key) {
  $urls[] = $BASE . "/catalog?cat=" . rawurlencode($key);
}

foreach ($productKeys as $key) {
  $urls[] = $BASE . "/product/" . rawurlencode($key);
}

$urls = array_values(array_unique($urls));

$today = gmdate("Y-m-d");

$xml = [];
$xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
$xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

foreach ($urls as $u) {
  $xml[] = "  <url>";
  $xml[] = "    <loc>" . xmlEscape($u) . "</loc>";
  $xml[] = "    <lastmod>{$today}</lastmod>";
  $xml[] = "  </url>";
}

$xml[] = "</urlset>";
$xmlStr = implode("\n", $xml) . "\n";

atomicWrite($outPath, $xmlStr);
atomicWrite($outGzPath, gzencode($xmlStr, 9));

echo "OK: wrote sitemap-data.xml (" . strlen($xmlStr) . " bytes)\n";
echo "OK: wrote sitemap-data.xml.gz\n";
