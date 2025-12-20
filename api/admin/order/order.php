<?php
// /api/admin/order/order.php

$mode = isset($_GET["mode"]) ? trim((string)$_GET["mode"]) : "list";

// ===== CACHE SETTINGS =====
$cacheTtl = 300; // 5 минут
$noCache  = isset($_GET["nocache"]) && $_GET["nocache"] == "1";

$rawCacheFile  = __DIR__ . "/evotor_products_raw_cache.json";
$listCacheFile = __DIR__ . "/evotor_level1_level2_cache.json";

// ===== EVOTOR SETTINGS =====
$token   = "59a62817-90d7-4ee2-8a35-92d0de7ac91f";
$storeId = "20230324-1379-4034-80CD-1581DAED4A6E";
$url = "https://api.evotor.ru/api/v1/inventories/stores/$storeId/products";

// ------------------ helpers ------------------

function json_out($arr, $httpCode = 200) {
    http_response_code($httpCode);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function normalize_key($name) {
    $name = trim((string)$name);
    if ($name === "") return "";
    $name = preg_replace('/\s+/u', ' ', $name);
    return mb_strtolower($name, "UTF-8");
}

function safe_filename($name) {
    $name = trim((string)$name);
    if ($name === "") $name = "export";
    $name = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $name);
    $name = preg_replace('/_+/u', '_', $name);
    return trim($name, "_");
}

/**
 * Ищем миниатюру товара в /photo_product_vitrina/
 * Файлы: <barcode>.webp, <barcode>_1.webp, <barcode>_2.webp ...
 * Возвращаем массив url, отсортированный.
 */
function findProductImages($barcode) {
    $barcode = trim((string)$barcode);
    if ($barcode === "") return [];

    $folder  = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_vitrina/";
    $urlBase = "/photo_product_vitrina/";
    if (!is_dir($folder)) return [];

    // быстрый путь: основной файл
    $main = $folder . $barcode . ".webp";
    if (is_file($main)) {
        return [$urlBase . $barcode . ".webp"];
    }

    // иначе ищем все варианты
    $mask  = $folder . $barcode . "*.webp";
    $files = glob($mask);
    if (!$files) return [];

    $images = [];
    foreach ($files as $path) $images[] = $urlBase . basename($path);
    sort($images);
    return $images;
}

function fetch_evotor_data($url, $token, $rawCacheFile, $cacheTtl, $noCache) {
    if (!$noCache && file_exists($rawCacheFile) && (time() - filemtime($rawCacheFile) < $cacheTtl)) {
        $raw = file_get_contents($rawCacheFile);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            header("X-From-Cache: 1");
            return $data;
        }
    }

    header("X-From-Cache: 0");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) json_out(["error" => "curl error", "details" => $curlErr], 500);
    if ($httpCode < 200 || $httpCode >= 300) {
        json_out(["error" => "evotor http error", "httpCode" => $httpCode, "body" => $response], 502);
    }

    $data = json_decode($response, true);
    if (!is_array($data)) json_out(["error" => "invalid json from evotor"], 500);

    @file_put_contents($rawCacheFile, $response);
    return $data;
}

function build_groups_and_depth($data) {
    $groups = [];

    foreach ($data as $item) {
        if (!empty($item["group"])) {
            $uuid = $item["uuid"] ?? null;
            if (!$uuid) continue;

            $groups[$uuid] = [
                "uuid"   => $uuid,
                "name"   => $item["name"] ?? "",
                "parent" => $item["parentUuid"] ?? null,
                "depth"  => null,
            ];
        }
    }

    $memo = [];
    $calcDepth = function($uuid) use (&$groups, &$memo, &$calcDepth) {
        if (isset($memo[$uuid])) return $memo[$uuid];
        if (!isset($groups[$uuid])) return $memo[$uuid] = null;

        $parent = $groups[$uuid]["parent"] ?? null;
        if (empty($parent) || !isset($groups[$parent])) return $memo[$uuid] = 1;

        $pd = $calcDepth($parent);
        if ($pd === null) return $memo[$uuid] = null;
        return $memo[$uuid] = $pd + 1;
    };

    foreach ($groups as $uuid => $g) $groups[$uuid]["depth"] = $calcDepth($uuid);
    return $groups;
}

function build_categories_and_contragents($groups) {
    $categories = [];
    foreach ($groups as $g) {
        if (($g["depth"] ?? null) === 1) {
            $categories[$g["uuid"]] = ["uuid" => $g["uuid"], "name" => $g["name"]];
        }
    }

    $contragentsMap = []; // key => {key,name, uuids[], categoryUuids[]}
    foreach ($groups as $g) {
        if (($g["depth"] ?? null) !== 2) continue;

        $name = trim((string)($g["name"] ?? ""));
        if ($name === "") continue;

        $key = normalize_key($name);
        if ($key === "") continue;

        $parentCatUuid = $g["parent"] ?? null;

        if (!isset($contragentsMap[$key])) {
            $contragentsMap[$key] = [
                "key" => $key,
                "name" => $name,
                "uuids" => [],
                "categoryUuids" => [],
            ];
        }

        $contragentsMap[$key]["uuids"][] = $g["uuid"];
        if ($parentCatUuid && isset($categories[$parentCatUuid])) {
            $contragentsMap[$key]["categoryUuids"][] = $parentCatUuid;
        }
    }

    foreach ($contragentsMap as &$c) {
        $c["uuids"] = array_values(array_unique($c["uuids"]));
        $c["categoryUuids"] = array_values(array_unique($c["categoryUuids"]));
    }
    unset($c);

    $categoriesList = array_values($categories);
    usort($categoriesList, fn($a,$b) => strnatcasecmp($a["name"], $b["name"]));

    $contragentsList = array_values($contragentsMap);
    usort($contragentsList, fn($a,$b) => strnatcasecmp($a["name"], $b["name"]));

    return [$categoriesList, $contragentsList, $contragentsMap];
}

function build_children_map($groups) {
    $children = [];
    foreach ($groups as $g) {
        $p = $g["parent"] ?? null;
        if (!$p) continue;
        $children[$p] ??= [];
        $children[$p][] = $g["uuid"];
    }
    return $children;
}

function collect_descendants($startUuid, $childrenMap) {
    $out = [];
    $stack = [$startUuid];

    while ($stack) {
        $u = array_pop($stack);
        if (isset($out[$u])) continue;
        $out[$u] = true;

        if (!empty($childrenMap[$u])) {
            foreach ($childrenMap[$u] as $ch) {
                if (!isset($out[$ch])) $stack[] = $ch;
            }
        }
    }
    return array_keys($out);
}

function get_product_article($item) {
    return isset($item["articleNumber"]) ? trim((string)$item["articleNumber"]) : "";
}


function get_product_quantity($item) {
    if (isset($item["quantity"]) && is_numeric($item["quantity"])) return $item["quantity"] + 0;
    return 0;
}

function get_product_barcodes($item) {
    $out = [];

    // Evotor: barCodes (массив строк)
    if (!empty($item["barCodes"]) && is_array($item["barCodes"])) {
        foreach ($item["barCodes"] as $b) {
            if (is_string($b)) {
                $b = trim($b);
                if ($b !== "") $out[] = $b;
            }
        }
    }

    // запасной вариант
    if (empty($out) && !empty($item["barcode"]) && is_string($item["barcode"])) {
        $b = trim($item["barcode"]);
        if ($b !== "") $out[] = $b;
    }

    return array_values(array_unique($out));
}

function output_xlsx($filenameBase, $rows) {
    // vendor в корне проекта:
    $autoload = __DIR__ . "/../../../vendor/autoload.php";
    if (!file_exists($autoload)) {
        json_out([
            "error" => "Composer autoload not found",
            "need"  => "install: composer require phpoffice/phpspreadsheet",
            "path"  => $autoload
        ], 500);
    }
    require_once $autoload;

    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        json_out([
            "error" => "PhpSpreadsheet not installed",
            "need"  => "composer require phpoffice/phpspreadsheet"
        ], 500);
    }

    $safe = safe_filename($filenameBase);
    $filename = $safe . ".xlsx";

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Export");

    // Порядок колонок: Наименование / Артикул / Остаток / Штрихкод
    $sheet->setCellValue("A1", "Наименование");
    $sheet->setCellValue("B1", "Артикул");
    $sheet->setCellValue("C1", "Остаток");
    $sheet->setCellValue("D1", "Штрихкод");

    $sheet->getStyle("A1:D1")->getFont()->setBold(true);
    $sheet->freezePane("A2");

    $r = 2;
    foreach ($rows as $row) {
        $sheet->setCellValue("A{$r}", (string)$row["name"]);
        $sheet->setCellValue("B{$r}", (string)$row["article"]);
        $sheet->setCellValue("C{$r}", $row["quantity"]);

        // Штрихкод как ТЕКСТ (никаких E+12)
        $sheet->setCellValueExplicit(
            "D{$r}",
            (string)$row["barcode"],
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );

        $r++;
    }

    $lastRow = max(1, $r - 1);

    // Колонка D (штрихкод) = текст
    if ($lastRow >= 2) {
        $sheet->getStyle("D2:D{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('@');
    }

    $sheet->setAutoFilter("A1:D{$lastRow}");

    // Авто-ширина (наименование будет под самый длинный текст)
    foreach (["A","B","C"] as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Для штрихкода лучше фикс ширину (и так остаётся текстом)
    $sheet->getColumnDimension("D")->setWidth(22);

    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

/**
 * Общая сборка rows (и для items и для export)
 */
function build_rows_for_contragent($data, $groups, $contr, $minMap) {
    $childrenMap = build_children_map($groups);

    // все группы контрагента: 2ур + все потомки
    $allowed = [];
    foreach (($contr["uuids"] ?? []) as $u2) {
        $desc = collect_descendants($u2, $childrenMap);
        foreach ($desc as $x) $allowed[$x] = true;
    }

    $seenProducts = [];
    $rows = [];

    foreach ($data as $item) {
        if (!empty($item["group"])) continue;

        $parent = $item["parentUuid"] ?? null;
        if (!$parent || !isset($allowed[$parent])) continue;

        $uuid = $item["uuid"] ?? null;
        if ($uuid && isset($seenProducts[$uuid])) continue;
        if ($uuid) $seenProducts[$uuid] = true;

        $barcodes = get_product_barcodes($item);
        if (empty($barcodes)) continue;

        // ищем баркод, который есть в product_min_stock
        $matchedBarcode = "";
        $minStock = null;
        foreach ($barcodes as $bc) {
            if (isset($minMap[$bc])) {
                $matchedBarcode = $bc;
                $minStock = $minMap[$bc];
                break;
            }
        }
        if ($matchedBarcode === "" || $minStock === null) continue;

        $qty = get_product_quantity($item);
        if ($qty > $minStock) continue; // только <= min_stock

        $images = findProductImages($matchedBarcode);
        $thumb  = $images[0] ?? "";

        $rows[] = [
            "uuid"      => (string)($uuid ?? ""),
            "name"      => (string)($item["name"] ?? ""),
            "article"   => (string)get_product_article($item),
            "quantity"  => $qty,
            "barcode"   => (string)$matchedBarcode,
            "min_stock" => $minStock,
            "image"     => $thumb, // ✅ миниатюра для сайта
        ];
    }

    usort($rows, fn($a,$b) => strnatcasecmp($a["name"], $b["name"]));
    return $rows;
}

// ------------------ main ------------------

if ($mode === "list") {
    if (!$noCache && file_exists($listCacheFile) && (time() - filemtime($listCacheFile) < $cacheTtl)) {
        header("Content-Type: application/json; charset=utf-8");
        header("X-From-Cache-List: 1");
        readfile($listCacheFile);
        exit;
    }
    header("X-From-Cache-List: 0");
}

$data = fetch_evotor_data($url, $token, $rawCacheFile, $cacheTtl, $noCache);
$groups = build_groups_and_depth($data);
[$categoriesList, $contragentsList, $contragentsMap] = build_categories_and_contragents($groups);

if ($mode === "list") {
    $out = json_encode([
        "categories"  => $categoriesList,
        "contragents" => $contragentsList,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    @file_put_contents($listCacheFile, $out);

    header("Content-Type: application/json; charset=utf-8");
    echo $out;
    exit;
}

// ===== NEW MODE: items (JSON для сайта) =====
if ($mode === "items") {
    require_once __DIR__ . "/../../db.php"; // $pdo

    // min_stock map: barcode => min_stock
    try {
        $minMap = [];
        $stmt = $pdo->query("SELECT barcode, min_stock FROM product_min_stock");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $bc = isset($row["barcode"]) ? trim((string)$row["barcode"]) : "";
            if ($bc === "") continue;
            $minMap[$bc] = is_numeric($row["min_stock"]) ? ($row["min_stock"] + 0) : 0;
        }
    } catch (Throwable $e) {
        json_out(["error" => "db error reading product_min_stock", "details" => $e->getMessage()], 500);
    }

    $key = isset($_GET["key"]) ? normalize_key($_GET["key"]) : "";
    if ($key === "" || !isset($contragentsMap[$key])) {
        json_out(["error" => "unknown contragent key", "key" => $key], 404);
    }

    $contr = $contragentsMap[$key];
    $contrName = $contr["name"] ?? $key;

    $rows = build_rows_for_contragent($data, $groups, $contr, $minMap);

    json_out([
        "contragent" => ["key" => $key, "name" => $contrName],
        "count"      => count($rows),
        "generatedAt"=> date("Y-m-d H:i:s"),
        "items"      => $rows
    ]);
}

// ===== EXPORT MODE (Excel) =====
if ($mode === "export") {
    require_once __DIR__ . "/../../db.php"; // $pdo

    // min_stock map: barcode => min_stock
    try {
        $minMap = [];
        $stmt = $pdo->query("SELECT barcode, min_stock FROM product_min_stock");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $bc = isset($row["barcode"]) ? trim((string)$row["barcode"]) : "";
            if ($bc === "") continue;
            $minMap[$bc] = is_numeric($row["min_stock"]) ? ($row["min_stock"] + 0) : 0;
        }
    } catch (Throwable $e) {
        json_out(["error" => "db error reading product_min_stock", "details" => $e->getMessage()], 500);
    }

    $key = isset($_GET["key"]) ? normalize_key($_GET["key"]) : "";
    if ($key === "" || !isset($contragentsMap[$key])) {
        json_out(["error" => "unknown contragent key", "key" => $key], 404);
    }

    $contr = $contragentsMap[$key];
    $contrName = $contr["name"] ?? $key;

    $rows = build_rows_for_contragent($data, $groups, $contr, $minMap);

    // Для excel: только нужные поля
    $xlsxRows = array_map(function($r){
        return [
            "name"     => $r["name"],
            "article"  => $r["article"],
            "quantity" => $r["quantity"],
            "barcode"  => $r["barcode"],
        ];
    }, $rows);

    output_xlsx("contragent_" . $contrName, $xlsxRows);
}

json_out(["error" => "unknown mode", "mode" => $mode], 400);
