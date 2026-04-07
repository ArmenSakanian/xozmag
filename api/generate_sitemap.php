<?php
// api/generate_sitemap.php
// Пишет:
// - ../sitemap-data.xml
// - ../sitemap-data.xml.gz
// - ../sitemap.xml (sitemap index)

declare(strict_types=1);

$BASE = "https://xozmag.ru";

$outDataPath   = __DIR__ . "/../sitemap-data.xml";
$outDataGzPath = __DIR__ . "/../sitemap-data.xml.gz";
$outIndexPath  = __DIR__ . "/../sitemap.xml";
$staticPath    = __DIR__ . "/../sitemap-static.xml";

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

function findFirstColumn(array $cols, array $candidates): ?string {
  foreach ($candidates as $candidate) {
    foreach ($cols as $col) {
      if (strtolower($col) === strtolower($candidate)) return $col;
    }
  }
  return null;
}

function normalizeUrlPath(string $path, string $prefix = ''): string {
  $path = trim($path);
  if ($path === '') return '';
  if (preg_match('~^https?://~i', $path)) return $path;

  $parsed = parse_url($path, PHP_URL_PATH);
  $path = is_string($parsed) && $parsed !== '' ? $parsed : $path;
  $path = '/' . ltrim($path, '/');

  if ($prefix !== '' && strpos($path, '/' . trim($prefix, '/')) !== 0) {
    $path = '/' . trim($prefix, '/') . '/' . ltrim($path, '/');
  }

  return $path;
}

function photoJsonToImages($raw, string $prefix = ''): array {
  $value = trim((string)$raw);
  if ($value === '' || $value === '[]') return [];

  $decoded = json_decode($value, true);
  $source = is_array($decoded) ? $decoded : [$value];

  $out = [];
  foreach ($source as $item) {
    $img = normalizeUrlPath((string)$item, $prefix);
    if ($img !== '') $out[] = $img;
  }

  return array_values(array_unique($out));
}

function bestLastmodValue(array $row, bool $hasUpdated, string $fallback = ''): string {
  if (!$hasUpdated) return $fallback;
  $raw = trim((string)($row['updated_at'] ?? ''));
  if ($raw === '') return $fallback;

  $ts = strtotime($raw);
  if ($ts === false) return $fallback;
  return gmdate('Y-m-d', $ts);
}

function fetchCategoryEntries(PDO $pdo, string $table, string $base, string $fallbackLastmod): array {
  $cols = tableColumns($pdo, $table);
  $updatedCol = findFirstColumn($cols, ['updated_at', 'modified_at', 'last_update', 'updated', 'created_at']);
  $slugCol = in_array('slug', $cols, true) ? 'slug' : null;
  $codeCol = in_array('code', $cols, true) ? 'code' : null;
  $photoCol = findFirstColumn($cols, ['photo_categories', 'photo']);

  $select = ['`id` AS id'];
  if ($slugCol) $select[] = "`$slugCol` AS slug";
  if ($codeCol) $select[] = "`$codeCol` AS code";
  if ($updatedCol) $select[] = "`$updatedCol` AS updated_at";
  if ($photoCol) $select[] = "`$photoCol` AS photo_raw";

  $stmt = $pdo->query('SELECT ' . implode(', ', $select) . ' FROM `' . str_replace('`', '``', $table) . '` ORDER BY id ASC');
  $out = [];

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $slug = trim((string)($row['slug'] ?? ''));
    $code = trim((string)($row['code'] ?? ''));
    $id = trim((string)($row['id'] ?? ''));
    $key = $slug !== '' ? $slug : ($code !== '' ? $code : $id);
    if ($key === '') continue;

    $images = [];
    if ($photoCol) {
      $prefix = strtolower($photoCol) === 'photo_categories' ? 'photo_categories_vitrina' : 'photo_product_vitrina';
      $images = photoJsonToImages($row['photo_raw'] ?? '', $prefix);
    }

    $out[] = [
      'loc' => $base . '/catalog?cat=' . rawurlencode($key),
      'lastmod' => bestLastmodValue($row, (bool)$updatedCol, $fallbackLastmod),
      'images' => array_map(fn($img) => preg_match('~^https?://~i', $img) ? $img : $base . $img, $images),
    ];
  }

  return $out;
}

function fetchProductEntries(PDO $pdo, string $table, string $base, string $fallbackLastmod): array {
  $cols = tableColumns($pdo, $table);
  $updatedCol = findFirstColumn($cols, ['updated_at', 'modified_at', 'last_update', 'updated', 'created_at']);
  $slugCol = in_array('slug', $cols, true) ? 'slug' : null;
  $photoCol = findFirstColumn($cols, ['photo']);

  $select = ['`id` AS id'];
  if ($slugCol) $select[] = "`$slugCol` AS slug";
  if ($updatedCol) $select[] = "`$updatedCol` AS updated_at";
  if ($photoCol) $select[] = "`$photoCol` AS photo_raw";

  $stmt = $pdo->query('SELECT ' . implode(', ', $select) . ' FROM `' . str_replace('`', '``', $table) . '` WHERE `id` IS NOT NULL');
  $out = [];

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = trim((string)($row['id'] ?? ''));
    $slug = trim((string)($row['slug'] ?? ''));
    $key = $slug !== '' ? $slug : $id;
    if ($key === '') continue;

    $images = $photoCol ? photoJsonToImages($row['photo_raw'] ?? '', 'photo_product_vitrina') : [];

    $out[] = [
      'loc' => $base . '/product/' . rawurlencode($key),
      'lastmod' => bestLastmodValue($row, (bool)$updatedCol, $fallbackLastmod),
      'images' => array_map(fn($img) => preg_match('~^https?://~i', $img) ? $img : $base . $img, $images),
    ];
  }

  return $out;
}

/* ================= main ================= */

[$productTable, $catTable] = findBestTables($pdo);

if (!$productTable) { echo "ERROR: не нашёл таблицу товаров\n"; exit(1); }
if (!$catTable) { echo "ERROR: не нашёл таблицу категорий\n"; exit(1); }

echo "Product table: {$productTable}\n";
echo "Category table: {$catTable}\n";

$today = gmdate("Y-m-d");
$productEntries = fetchProductEntries($pdo, $productTable, $BASE, $today);
$catEntries     = fetchCategoryEntries($pdo, $catTable, $BASE, $today);

echo "Products: " . count($productEntries) . "
";
echo "Categories: " . count($catEntries) . "
";

$entries = [
  ["loc" => $BASE . "/", "lastmod" => $today, "images" => []],
  ["loc" => $BASE . "/catalog", "lastmod" => $today, "images" => []],
  ...$catEntries,
  ...$productEntries,
];

$seen = [];
$hasImages = false;
foreach ($entries as $entry) {
  if (!empty($entry["images"])) {
    $hasImages = true;
    break;
  }
}

$xml = [];
$xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
$xml[] = $hasImages
  ? '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'
  : '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

foreach ($entries as $entry) {
  $loc = (string)($entry["loc"] ?? "");
  if ($loc === "" || isset($seen[$loc])) continue;
  $seen[$loc] = true;

  $xml[] = "  <url>";
  $xml[] = "    <loc>" . xmlEscape($loc) . "</loc>";
  $xml[] = "    <lastmod>" . xmlEscape((string)($entry["lastmod"] ?? $today)) . "</lastmod>";

  foreach ((array)($entry["images"] ?? []) as $img) {
    $xml[] = "    <image:image>";
    $xml[] = "      <image:loc>" . xmlEscape((string)$img) . "</image:loc>";
    $xml[] = "    </image:image>";
  }

  $xml[] = "  </url>";
}

$xml[] = "</urlset>";
$xmlStr = implode("
", $xml) . "
";

atomicWrite($outDataPath, $xmlStr);
atomicWrite($outDataGzPath, gzencode($xmlStr, 9));

echo "OK: wrote sitemap-data.xml (" . strlen($xmlStr) . " bytes)\n";
echo "OK: wrote sitemap-data.xml.gz\n";

// sitemap index (авто-обновление lastmod)
$staticLastmod = file_exists($staticPath) ? gmdate("Y-m-d", filemtime($staticPath)) : $today;
$dataLastmod   = file_exists($outDataGzPath) ? gmdate("Y-m-d", filemtime($outDataGzPath)) : $today;

$idx = [];
$idx[] = '<?xml version="1.0" encoding="UTF-8"?>';
$idx[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
$idx[] = '  <sitemap>';
$idx[] = '    <loc>' . xmlEscape($BASE . '/sitemap-static.xml') . '</loc>';
$idx[] = '    <lastmod>' . $staticLastmod . '</lastmod>';
$idx[] = '  </sitemap>';
$idx[] = '  <sitemap>';
$idx[] = '    <loc>' . xmlEscape($BASE . '/sitemap-data.xml.gz') . '</loc>';
$idx[] = '    <lastmod>' . $dataLastmod . '</lastmod>';
$idx[] = '  </sitemap>';
$idx[] = '</sitemapindex>';

atomicWrite($outIndexPath, implode("\n", $idx) . "\n");
echo "OK: wrote sitemap.xml (index)\n";
