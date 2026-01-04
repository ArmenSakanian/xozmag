<?php
// api/vitrina/evotor_catalog_core.php
declare(strict_types=1);

/**
 * Возвращает:
 * [
 *   'ok' => bool,
 *   'from_cache' => bool,
 *   'duplicates_found' => int,
 *   'payload' => array|null,
 *   'json' => string|null,
 *   'error' => string|null,
 *   'http' => int|null,
 *   'details' => string|null,
 * ]
 */
function evotor_catalog_build(array $opts = []): array
{
  // === НАСТРОЙКИ ФАЙЛОВОГО КЭША ===
  $cacheFile = __DIR__ . "/evotor_catalog_cache.json";
  $cacheTtl  = isset($opts["cache_ttl"]) ? (int)$opts["cache_ttl"] : 1;

  $nocache = !empty($opts["nocache"]);
  $debug   = !empty($opts["debug"]);

  // --- Кэш свежий? ---
  if (!$nocache && is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    $json = @file_get_contents($cacheFile);
    $payload = $json ? json_decode($json, true) : null;

    if (is_array($payload)) {
      return [
        "ok" => true,
        "from_cache" => true,
        "duplicates_found" => 0, // в кэше это не считаем (не критично)
        "payload" => $payload,
        "json" => $json,
        "error" => null,
        "http" => null,
        "details" => null,
      ];
    }
    // если кэш битый - идём получать заново
  }

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

  // Если эвотор недоступен - попробуем отдать старый кэш (если есть)
  if ($curlErr || $httpCode >= 400) {
    if (is_file($cacheFile)) {
      $json = @file_get_contents($cacheFile);
      $payload = $json ? json_decode($json, true) : null;

      if (is_array($payload)) {
        return [
          "ok" => true,
          "from_cache" => true,
          "duplicates_found" => 0,
          "payload" => $payload,
          "json" => $json,
          "error" => null,
          "http" => null,
          "details" => null,
        ];
      }
    }

    return [
      "ok" => false,
      "from_cache" => false,
      "duplicates_found" => 0,
      "payload" => null,
      "json" => null,
      "error" => "evotor_request_failed",
      "http" => $httpCode,
      "details" => $curlErr ?: (string)$response,
    ];
  }

  $data = json_decode((string)$response, true);
  if (!is_array($data)) {
    if (is_file($cacheFile)) {
      $json = @file_get_contents($cacheFile);
      $payload = $json ? json_decode($json, true) : null;

      if (is_array($payload)) {
        return [
          "ok" => true,
          "from_cache" => true,
          "duplicates_found" => 0,
          "payload" => $payload,
          "json" => $json,
          "error" => null,
          "http" => null,
          "details" => null,
        ];
      }
    }

    return [
      "ok" => false,
      "from_cache" => false,
      "duplicates_found" => 0,
      "payload" => null,
      "json" => null,
      "error" => "invalid_data_from_evotor",
      "http" => $httpCode,
      "details" => "json_decode_failed",
    ];
  }

  /* =========================
     helpers (как у тебя)
  ========================= */
  $docRoot = rtrim((string)($_SERVER["DOCUMENT_ROOT"] ?? ""), "/");

  $safe_rel_from_url_or_path = function($p) {
    $p = trim((string)$p);
    if ($p === "") return "";
    if (preg_match('~^https?://~i', $p)) {
      $u = parse_url($p);
      return !empty($u["path"]) ? $u["path"] : "";
    }
    return $p;
  };

  $findProductImages = function($barcode) use ($docRoot) {
    $barcode = trim((string)$barcode);
    if ($barcode === "" || $docRoot === "") return [];

    $folder  = $docRoot . "/photo_product_vitrina/";
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
    sort($images);
    return $images;
  };

  $extractBrand = function($name) {
    $name = (string)$name;
    if (!preg_match('/\(([^()]*)\)\s*$/u', $name, $m)) return "";

    $value = trim($m[1]);
    if ($value === "") return "";

    if (preg_match('/^\d+(\s*(см|mm|м|l|л|шт|g|гр))?$/iu', $value)) return "";

    return mb_convert_case($value, MB_CASE_TITLE, "UTF-8");
  };

  $productScore = function($p) {
    $score = 0;

    $price = (float)($p["price"] ?? 0);
    $qty   = (float)($p["quantity"] ?? 0);

    $name  = trim((string)($p["name"] ?? ""));
    $brand = trim((string)($p["brandName"] ?? ""));
    $desc  = trim((string)($p["description"] ?? ""));
    $art   = trim((string)($p["article"] ?? ""));
    $imgs  = is_array($p["images"] ?? null) ? count(array_filter($p["images"])) : 0;

    if ($price > 0) $score += 100000;
    if ($qty > 0)   $score += 1000;
    if ($desc !== "")  $score += 200;
    if ($brand !== "") $score += 100;
    if ($art !== "")   $score += 50;
    if ($name !== "")  $score += 20;
    $score += min(20, $imgs) * 5;

    $score += min(30, mb_strlen($name, "UTF-8"));

    return $score;
  };

  $pickBestProduct = function($a, $b) use ($productScore) {
    $sa = $productScore($a);
    $sb = $productScore($b);

    if ($sa > $sb) return $a;
    if ($sb > $sa) return $b;

    $ua = (string)($a["uuid"] ?? "");
    $ub = (string)($b["uuid"] ?? "");
    return ($ua <= $ub) ? $a : $b;
  };

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
  $getDepthMemo = function($uuid) use (&$groups, &$depthMemo) {
    $uuid = (string)$uuid;
    if ($uuid === "" || !isset($groups[$uuid])) return 1;
    if (isset($depthMemo[$uuid])) return $depthMemo[$uuid];

    $depth = 1;
    $cur = $uuid;
    $guard = 0;

    while (!empty($groups[$cur]["parent"]) && isset($groups[$cur]["parent"])) {
      $parent = (string)$groups[$cur]["parent"];
      if ($parent === "" || !isset($groups[$parent])) break;
      $depth++;
      $cur = $parent;
      if (++$guard > 50) break;
    }

    $depthMemo[$uuid] = $depth;
    return $depth;
  };

  foreach ($groups as $uuid => $g) {
    $groups[$uuid]["depth"] = $getDepthMemo($uuid);
  }

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
  $dupesByBarcode = [];

  foreach ($productsRaw as $p) {
    $barcodes = $p["barCodes"] ?? [];
    $barcode = is_array($barcodes) ? (string)($barcodes[0] ?? "") : (string)$barcodes;
    $barcode = trim($barcode);
    if ($barcode === "") continue;

    $title = (string)($p["name"] ?? "");
    $brandName = $extractBrand($title);

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
      if (++$guard > 50) break;
    }

    $images = $findProductImages($barcode);

$item = [
  "uuid"         => (string)($p["uuid"] ?? ""),
  "name"         => $title,
  "price"        => $p["price"] ?? 0,
  "quantity"     => $p["quantity"] ?? 0,
  "measureName"  => (string)($p["measureName"] ?? ""), // ✅ добавили
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
      $dupesByBarcode[$barcode][] = $item;
      $byBarcode[$barcode] = $pickBestProduct($byBarcode[$barcode], $item);
    }
  }

  $resultProducts = array_values($byBarcode);

  $brandList = [];
  foreach ($resultProducts as $rp) {
    $b = trim((string)($rp["brandName"] ?? ""));
    if ($b !== "") $brandList[] = $b;
  }
  $brandsUnique = array_values(array_unique($brandList));
  sort($brandsUnique);

  $payload = [
    "categories" => array_values($categories),
    "types"      => array_values($types),
    "brands"     => $brandsUnique,
    "products"   => $resultProducts
  ];

  if ($debug) {
    $dbg = [];
    $i = 0;
    foreach ($dupesByBarcode as $bc => $items) {
      $dbg[$bc] = array_slice($items, 0, 5);
      if (++$i >= 200) break;
    }
    $payload["_debug_duplicates_barcodes_count"] = count($dupesByBarcode);
    $payload["_debug_duplicates_samples"] = $dbg;
  }

  $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

  // атомарная запись кэша
  $tmp = $cacheFile . ".tmp";
  @file_put_contents($tmp, (string)$json, LOCK_EX);
  @rename($tmp, $cacheFile);

  return [
    "ok" => true,
    "from_cache" => false,
    "duplicates_found" => count($dupesByBarcode),
    "payload" => $payload,
    "json" => (string)$json,
    "error" => null,
    "http" => null,
    "details" => null,
  ];
}
