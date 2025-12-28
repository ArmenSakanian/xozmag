<?php
// api/generate_sitemap.php
// Запуск: php api/generate_sitemap.php
// Результат: ../sitemap.xml и ../sitemap.xml.gz

declare(strict_types=1);

$BASE = "https://xozmag.ru";
$outPath = __DIR__ . "/../sitemap.xml";
$outGzPath = __DIR__ . "/../sitemap.xml.gz";

require_once __DIR__ . "/db.php"; // должен создать $pdo (PDO)

if (!isset($pdo) || !($pdo instanceof PDO)) {
  fwrite(STDERR, "ERROR: \$pdo не найден. Проверь api/db.php\n");
  exit(1);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

    // --- score product table ---
    $scoreP = 0;
    if (in_array("id", $cols, true)) $scoreP += 5;
    foreach (["price","cost","sku","article","barcode","brand","title","name","category_id","cat_id"] as $c) {
      if (in_array($c, $cols, true)) $scoreP += 2;
    }
    if ($scoreP > $bestProduct["score"]) $bestProduct = ["name" => $t, "score" => $scoreP];

    // --- score category table ---
    $scoreC = 0;
    if (in_array("id", $cols, true)) $scoreC += 4;
    if (in_array("name", $cols, true) || in_array("title", $cols, true)) $scoreC += 4;
    foreach (["parent_id","parent","parentid","cat_parent","level"] as $c) {
      if (in_array($c, $cols, true)) $scoreC += 2;
    }
    // чтобы не выбрать product как category
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
    // только числа (чтобы не поломать урлы)
    if ($id !== "" && ctype_digit($id)) $ids[] = $id;
  }
  return $ids;
}

function xmlEscape(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, "UTF-8");
}

[$productTable, $catTable] = findBestTables($pdo);

if (!$productTable) { fwrite(STDERR, "ERROR: не нашёл таблицу товаров\n"); exit(1); }
if (!$catTable) { fwrite(STDERR, "ERROR: не нашёл таблицу категорий\n"); exit(1); }

echo "Product table: {$productTable}\n";
echo "Category table: {$catTable}\n";

$productIds = fetchIds($pdo, $productTable, "id");
$catIds = fetchIds($pdo, $catTable, "id");

echo "Products: " . count($productIds) . "\n";
echo "Categories: " . count($catIds) . "\n";

$urls = [];
$urls[] = $BASE . "/";
$urls[] = $BASE . "/catalog";

foreach ($catIds as $id) {
  $urls[] = $BASE . "/catalog?cat=" . $id;
}
foreach ($productIds as $id) {
  $urls[] = $BASE . "/product/" . $id;
}

$urls = array_values(array_unique($urls));

$xml = [];
$xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
$xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$today = gmdate("Y-m-d");
foreach ($urls as $u) {
  $xml[] = "  <url>";
  $xml[] = "    <loc>" . xmlEscape($u) . "</loc>";
  $xml[] = "    <lastmod>{$today}</lastmod>";
  $xml[] = "  </url>";
}

$xml[] = "</urlset>";
$xmlStr = implode("\n", $xml) . "\n";

file_put_contents($outPath, $xmlStr);
file_put_contents($outGzPath, gzencode($xmlStr, 9));

echo "OK: wrote sitemap.xml (" . strlen($xmlStr) . " bytes)\n";
echo "OK: wrote sitemap.xml.gz\n";
