<?php
header("Content-Type: application/json; charset=utf-8");

// === ЭВОТОР ДАННЫЕ ===
$token = "59a62817-90d7-4ee2-8a35-92d0de7ac91f";
$storeId = "20230324-1379-4034-80CD-1581DAED4A6E";

$url = "https://api.evotor.ru/api/v1/inventories/stores/$storeId/products";

// --- CURL ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!is_array($data)) {
    echo json_encode(["error" => "invalid data"]);
    exit;
}

// =======================================================================
//  🔥  ФУНКЦИЯ ПОИСКА ВСЕХ ФОТОГРАФИЙ ТОВАРА
// =======================================================================

function findProductImages($barcode) {
    if (!$barcode) return [];

    $folder = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_vitrina/";
    $urlBase = "/photo_product_vitrina/";

    if (!is_dir($folder)) return [];

    $extensions = ["jpg", "jpeg", "png", "webp"];
    $images = [];

    foreach (scandir($folder) as $file) {
        $path = $folder . $file;

        if (!is_file($path)) continue;

        // основное фото без суффикса
        foreach ($extensions as $ext) {
            if ($file === "{$barcode}.{$ext}") {
                $images[1] = $urlBase . "{$barcode}.webp";
            }
        }

        // варианты _2, _3, _10 ...
        if (preg_match("/^{$barcode}_(\d+)\.(jpg|jpeg|png|webp)$/i", $file, $m)) {
            $num = intval($m[1]);
            $images[$num] = $urlBase . "{$barcode}_{$num}.webp";
        }
    }

    // сортируем по номеру фото
    ksort($images);

    return array_values($images);
}

// =======================================================================
// 1) Разделяем группы и товары
// =======================================================================

$groups = [];
$products = [];

foreach ($data as $item) {
    if (!empty($item["group"])) {
        $groups[$item["uuid"]] = [
            "uuid"        => $item["uuid"],
            "name"        => $item["name"],
            "parent"      => $item["parentUuid"] ?? null,
            "children"    => [],
            "depth"       => null
        ];
    } else {
        $products[] = $item;
    }
}

// =======================================================================
// 2) Глубина групп
// =======================================================================

function getDepth($uuid, $groups) {
    $depth = 1;
    while (!empty($groups[$uuid]["parent"])) {
        $uuid = $groups[$uuid]["parent"];
        $depth++;
    }
    return $depth;
}

foreach ($groups as $uuid => &$g) {
    $g["depth"] = getDepth($uuid, $groups);
}
unset($g);

// =======================================================================
// 3) Категории, бренды, типы
// =======================================================================

$categories = [];
$brands = [];
$types = [];

foreach ($groups as $g) {
    if ($g["depth"] === 1) {
        $categories[$g["uuid"]] = [
            "uuid" => $g["uuid"],
            "name" => $g["name"]
        ];
    }
    elseif ($g["depth"] === 3) {
        $brands[$g["uuid"]] = [
            "uuid" => $g["uuid"],
            "name" => $g["name"]
        ];
    }
    elseif ($g["depth"] >= 4) {
        $types[$g["uuid"]] = [
            "uuid" => $g["uuid"],
            "name" => $g["name"]
        ];
    }
}

// =======================================================================
// 4) Привязка товаров
// =======================================================================

function getVirtualType() {
    return [
        "uuid" => "type-other",
        "name" => "Разное"
    ];
}

$virtualType = getVirtualType();
$types[$virtualType["uuid"]] = $virtualType;

$resultProducts = [];

foreach ($products as $p) {

    if (empty($p["parentUuid"])) continue;

    $gid = $p["parentUuid"];

    $chain = [];
    $current = $gid;

    while (!empty($current) && isset($groups[$current])) {
        $chain[] = $current;
        $current = $groups[$current]["parent"];
    }

    usort($chain, function($a, $b) use ($groups) {
        return $groups[$a]["depth"] <=> $groups[$b]["depth"];
    });

    $cat = null;
    $brand = null;
    $type = null;

    foreach ($chain as $uuid) {
        $depth = $groups[$uuid]["depth"];

        if ($depth === 1) $cat = $uuid;
        elseif ($depth === 3) $brand = $uuid;
        elseif ($depth >= 4) $type = $uuid;
    }

    if (!$type) $type = $virtualType["uuid"];

    // штрихкод
    $barcode = $p["barCodes"][0] ?? "";

    // 🔥 НАХОДИМ ВСЕ ФОТО ТОВАРА
    $images = findProductImages($barcode);

    // финальный объект товара
    $resultProducts[] = [
        "uuid"      => $p["uuid"],
        "name"      => $p["name"],
        "price"     => $p["price"] ?? 0,
        "quantity"  => $p["quantity"] ?? 0,
        "barcode"   => $barcode,
        "article"   => $p["articleNumber"] ?? "",
        "categoryUuid" => $cat,
        "brandUuid"    => $brand,
        "typeUuid"     => $type,

        // 🔥 теперь массив изображений
        "images"       => $images  
    ];
}

// =======================================================================
// 5) JSON вывод
// =======================================================================

echo json_encode([
    "categories" => array_values($categories),
    "brands"     => array_values($brands),
    "types"      => array_values($types),
    "products"   => $resultProducts
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
