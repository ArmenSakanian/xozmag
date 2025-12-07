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
// ФУНКЦИЯ поиска изображений
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

        foreach ($extensions as $ext) {
            if ($file === "{$barcode}.{$ext}") {
                $images[] = $urlBase . "{$barcode}.webp";
            }
        }

        if (preg_match("/^{$barcode}_(\d+)\.(jpg|jpeg|png|webp)$/i", $file, $m)) {
            $images[] = $urlBase . "{$barcode}_{$m[1]}.webp";
        }
    }

    return $images;
}

// =======================================================================
// Разделяем группы и товары
// =======================================================================

$groups = [];
$productsRaw = [];

foreach ($data as $item) {
    if (!empty($item["group"])) {
        $groups[$item["uuid"]] = [
            "uuid"   => $item["uuid"],
            "name"   => $item["name"],
            "parent" => $item["parentUuid"] ?? null,
            "depth"  => null
        ];
    } else {
        $productsRaw[] = $item;
    }
}

// =======================================================================
// Расчёт глубины (depth)
// =======================================================================

function getDepth($uuid, $groups) {
    $depth = 1;
    while (!empty($groups[$uuid]["parent"]) && isset($groups[$uuid]["parent"])) {
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
// Категории и Типы (Новая Логика)
// =======================================================================

$categories = []; // depth = 1
$types = [];      // depth = 3

foreach ($groups as $g) {
    if ($g["depth"] === 1) {
        $categories[$g["uuid"]] = [
            "uuid" => $g["uuid"],
            "name" => $g["name"]
        ];
    }

    if ($g["depth"] === 3) {   // НОВОЕ: depth=3 = типы товаров
        $types[$g["uuid"]] = [
            "uuid" => $g["uuid"],
            "name" => $g["name"]
        ];
    }
}

// =======================================================================
// Функция извлечения бренда из названия
// =======================================================================

function extractBrand($name) {

    // ищем ТОЛЬКО ПОСЛЕДНИЕ скобки в конце строки
    if (!preg_match('/\(([^()]*)\)\s*$/u', $name, $m)) {
        return "";
    }

    $value = trim($m[1]);

    // если там цифры, размер, объём — это НЕ бренд
    if (preg_match('/^\d+(\s*(см|mm|м|l|л|шт|g|гр))?$/iu', $value)) {
        return "";
    }

    // первая буква большая
    return mb_convert_case($value, MB_CASE_TITLE, "UTF-8");
}


// =======================================================================
// Финальная сборка товаров
// =======================================================================

$resultProducts = [];
$brandList = [];

foreach ($productsRaw as $p) {

    $barcode = $p["barCodes"][0] ?? "";
    $title = $p["name"] ?? "";

    // Определяем бренд именем
    $brandName = extractBrand($title);

    if ($brandName !== "") {
        $brandList[] = $brandName;
    }

    // Определяем категорию (depth=1) и тип продукции (depth=3)
    $catUuid = null;
    $catName = null;
    $typeUuid = null;
    $typeName = null;

    $current = $p["parentUuid"];

    while ($current && isset($groups[$current])) {
        $depth = $groups[$current]["depth"];

        if ($depth === 1) {
            $catUuid = $groups[$current]["uuid"];
            $catName = $groups[$current]["name"];
        }

        if ($depth === 3) {
            $typeUuid = $groups[$current]["uuid"];
            $typeName = $groups[$current]["name"];
        }

        $current = $groups[$current]["parent"];
    }

    $images = findProductImages($barcode);

    $resultProducts[] = [
        "uuid"         => $p["uuid"],
        "name"         => $title,
        "price"        => $p["price"] ?? 0,
        "quantity"     => $p["quantity"] ?? 0,
        "barcode"      => $barcode,
        "article"      => $p["articleNumber"] ?? "",
        "brandName"    => $brandName,
        "categoryUuid" => $catUuid,
        "categoryName" => $catName,
        "typeUuid"     => $typeUuid,
        "typeName"     => $typeName,
        "images"       => $images
    ];
}

// =======================================================================
// Уникальные бренды
// =======================================================================

$brandsUnique = array_values(array_unique(array_filter($brandList)));

// =======================================================================
// JSON вывод
// =======================================================================

echo json_encode([
    "categories" => array_values($categories),
    "types"      => array_values($types),
    "brands"     => $brandsUnique,
    "products"   => $resultProducts
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
