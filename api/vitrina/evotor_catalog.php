<?php
header("Content-Type: application/json; charset=utf-8");

// чтобы внешние прокси/браузер не кешировали (у тебя кеш только файловый)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// === НАСТРОЙКИ ФАЙЛОВОГО КЭША ===
$cacheFile = __DIR__ . "/evotor_catalog_cache.json";
$cacheTtl  = 1; // 5 минут (можешь поставить 2 для теста)

// флаги режима
$nocache = isset($_GET["nocache"]) && $_GET["nocache"] === "1";
$debug   = isset($_GET["debug"])   && $_GET["debug"] === "1";

// --- Если кэш свежий, отдаем его и выходим ---
if (!$nocache && file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
  header("X-From-Cache: 1");
  readfile($cacheFile);
  exit;
}
header("X-From-Cache: 0");

// === ЭВОТОР ДАННЫЕ ===
$token   = "59a62817-90d7-4ee2-8a35-92d0de7ac91f";
$storeId = "20230324-1379-4034-80CD-1581DAED4A6E";

$url = "https://api.evotor.ru/api/v1/inventories/stores/$storeId/products";

// --- CURL ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer $token",
  "Accept: application/json",
  "Cache-Control: no-cache",
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr || $httpCode >= 400) {
  // если Эвотор недоступен, а кэш уже есть — отдаем старый кэш
  if (file_exists($cacheFile)) {
    readfile($cacheFile);
    exit;
  }
  echo json_encode([
    "error" => "evotor_request_failed",
    "http"  => $httpCode,
    "details" => $curlErr ?: $response
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode($response, true);

if (!is_array($data)) {
  if (file_exists($cacheFile)) {
    readfile($cacheFile);
    exit;
  }
  echo json_encode(["error" => "invalid data from evotor"], JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================
   helpers
========================= */
function safe_rel_from_url_or_path($p) {
  $p = trim((string)$p);
  if ($p === "") return "";
  if (preg_match('~^https?://~i', $p)) {
    $u = parse_url($p);
    return !empty($u["path"]) ? $u["path"] : "";
  }
  return $p;
}

function findProductImages($barcode) {
  $barcode = trim((string)$barcode);
  if ($barcode === "") return [];

  $folder  = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . "/photo_product_vitrina/";
  $urlBase = "/photo_product_vitrina/";

  if (!is_dir($folder)) return [];

  $mask  = $folder . $barcode . "*.webp";
  $files = glob($mask);
  if (!$files) return [];

  $images = [];
  foreach ($files as $path) {
    $images[] = $urlBase . basename($path);
  }

  $images = array_values(array_unique($images));
  sort($images); // стабильный порядок
  return $images;
}

function extractBrand($name) {
  $name = (string)$name;
  if (!preg_match('/\(([^()]*)\)\s*$/u', $name, $m)) return "";

  $value = trim($m[1]);
  if ($value === "") return "";

  // если там цифры/размер/объём — это не бренд
  if (preg_match('/^\d+(\s*(см|mm|м|l|л|шт|g|гр))?$/iu', $value)) return "";

  return mb_convert_case($value, MB_CASE_TITLE, "UTF-8");
}

// детерминированный "скоринг" — чтобы всегда выбирать одну и ту же карточку на barcode
function productScore($p) {
  $score = 0;

  $price = (float)($p["price"] ?? 0);
  $qty   = (float)($p["quantity"] ?? 0);

  $name  = trim((string)($p["name"] ?? ""));
  $brand = trim((string)($p["brandName"] ?? ""));
  $desc  = trim((string)($p["description"] ?? ""));
  $art   = trim((string)($p["article"] ?? ""));
  $imgs  = is_array($p["images"] ?? null) ? count(array_filter($p["images"])) : 0;

  // приоритеты (можешь менять)
  if ($price > 0) $score += 100000;   // цена обычно ключевая
  if ($qty > 0)   $score += 1000;
  if ($desc !== "")  $score += 200;
  if ($brand !== "") $score += 100;
  if ($art !== "")   $score += 50;
  if ($name !== "")  $score += 20;
  $score += min(20, $imgs) * 5;

  // чуть-чуть за длину имени (иногда одна версия обрезана)
  $score += min(30, mb_strlen($name, "UTF-8"));

  return $score;
}

function pickBestProduct($a, $b) {
  $sa = productScore($a);
  $sb = productScore($b);

  if ($sa > $sb) return $a;
  if ($sb > $sa) return $b;

  // tie-breaker: стабильный выбор по uuid (лексикографически)
  $ua = (string)($a["uuid"] ?? "");
  $ub = (string)($b["uuid"] ?? "");
  return ($ua <= $ub) ? $a : $b;
}

/* =========================
   Разделяем группы и товары
========================= */
$groups      = [];
$productsRaw = [];

foreach ($data as $item) {
  if (!empty($item["group"])) {
    $uuid = (string)($item["uuid"] ?? "");
    if ($uuid === "") continue;

    $groups[$uuid] = [
      "uuid"   => $uuid,
      "name"   => (string)($item["name"] ?? ""),
      "parent" => $item["parentUuid"] ?? null,
      "depth"  => null
    ];
  } else {
    $productsRaw[] = $item;
  }
}

/* =========================
   depth (с мемоизацией)
========================= */
$depthMemo = [];
function getDepthMemo($uuid, $groups, &$memo) {
  $uuid = (string)$uuid;
  if ($uuid === "" || !isset($groups[$uuid])) return 1;
  if (isset($memo[$uuid])) return $memo[$uuid];

  $depth = 1;
  $cur = $uuid;
  $guard = 0;

  while (!empty($groups[$cur]["parent"]) && isset($groups[$cur]["parent"])) {
    $parent = (string)$groups[$cur]["parent"];
    if ($parent === "" || !isset($groups[$parent])) break;
    $depth++;
    $cur = $parent;

    // защита от циклов
    if (++$guard > 50) break;
  }

  $memo[$uuid] = $depth;
  return $depth;
}

foreach ($groups as $uuid => &$g) {
  $g["depth"] = getDepthMemo($uuid, $groups, $depthMemo);
}
unset($g);

/* =========================
   Категории (depth=1) и Типы (depth=3)
========================= */
$categories = [];
$types      = [];

foreach ($groups as $g) {
  if ((int)$g["depth"] === 1) {
    $categories[$g["uuid"]] = ["uuid" => $g["uuid"], "name" => $g["name"]];
  }
  if ((int)$g["depth"] === 3) {
    $types[$g["uuid"]] = ["uuid" => $g["uuid"], "name" => $g["name"]];
  }
}

/* =========================
   Финальная сборка товаров (С ДЕДУПОМ ПО BARCODE)
========================= */
$byBarcode = [];
$dupesByBarcode = []; // только для debug

foreach ($productsRaw as $p) {
  $barcodes = $p["barCodes"] ?? [];
  $barcode = is_array($barcodes) ? (string)($barcodes[0] ?? "") : (string)$barcodes;
  $barcode = trim($barcode);
  if ($barcode === "") continue;

  $title = (string)($p["name"] ?? "");

  $brandName = extractBrand($title);

  // категория и тип
  $catUuid  = null;
  $catName  = null;
  $typeUuid = null;
  $typeName = null;

  $current = $p["parentUuid"] ?? null;
  $guard = 0;

  while ($current && isset($groups[$current])) {
    $depth = (int)($groups[$current]["depth"] ?? 0);

    if ($depth === 1) {
      $catUuid = $groups[$current]["uuid"];
      $catName = $groups[$current]["name"];
    }

    if ($depth === 3) {
      $typeUuid = $groups[$current]["uuid"];
      $typeName = $groups[$current]["name"];
    }

    $current = $groups[$current]["parent"] ?? null;

    if (++$guard > 50) break; // защита
  }

  $images = findProductImages($barcode);

  $item = [
    "uuid"         => (string)($p["uuid"] ?? ""),
    "name"         => $title,
    "price"        => $p["price"] ?? 0,
    "quantity"     => $p["quantity"] ?? 0,
    "barcode"      => $barcode,
    "article"      => (string)($p["articleNumber"] ?? ""),
    "brandName"    => $brandName,
    "description"  => (string)($p["description"] ?? ""),
    "categoryUuid" => $catUuid,
    "categoryName" => $catName,
    "typeUuid"     => $typeUuid,
    "typeName"     => $typeName,
    "images"       => $images
  ];

  if (!isset($byBarcode[$barcode])) {
    $byBarcode[$barcode] = $item;
  } else {
    // запомним для дебага
    $dupesByBarcode[$barcode][] = $item;

    // выберем одну "лучшую" детерминированно
    $byBarcode[$barcode] = pickBestProduct($byBarcode[$barcode], $item);
  }
}

// финальный список (уникальный по barcode)
$resultProducts = array_values($byBarcode);

// бренды уже из финального списка (а не из дублей)
$brandList = [];
foreach ($resultProducts as $rp) {
  $b = trim((string)($rp["brandName"] ?? ""));
  if ($b !== "") $brandList[] = $b;
}
$brandsUnique = array_values(array_unique($brandList));
sort($brandsUnique);

// заголовок: сколько баркодов с дублями нашли
header("X-Duplicates-Found: " . count($dupesByBarcode));

/* =========================
   JSON вывод + запись в кэш
========================= */
$payload = [
  "categories" => array_values($categories),
  "types"      => array_values($types),
  "brands"     => $brandsUnique,
  "products"   => $resultProducts
];

if ($debug) {
  // покажем первые 200 дублей (чтобы не раздувать JSON)
  $dbg = [];
  $i = 0;
  foreach ($dupesByBarcode as $bc => $items) {
    $dbg[$bc] = array_slice($items, 0, 5);
    if (++$i >= 200) break;
  }
  $payload["_debug_duplicates_barcodes_count"] = count($dupesByBarcode);
  $payload["_debug_duplicates_samples"] = $dbg;
}

$out = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// атомарная запись кэша
$tmp = $cacheFile . ".tmp";
file_put_contents($tmp, $out, LOCK_EX);
@rename($tmp, $cacheFile);

echo $out;
