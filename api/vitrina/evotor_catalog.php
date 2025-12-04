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
            "depth"       => null // определим позже
        ];
    } else {
        $products[] = $item;
    }
}

// =======================================================================
// 2) Строим дерево + определяем глубину каждой группы
// =======================================================================

// Рекурсивная функция определения глубины
function getDepth($uuid, $groups) {
    $depth = 1;
    while (!empty($groups[$uuid]["parent"])) {
        $uuid = $groups[$uuid]["parent"];
        $depth++;
    }
    return $depth;
}

// Вычисляем глубину для всех групп
foreach ($groups as $uuid => &$g) {
    $g["depth"] = getDepth($uuid, $groups);
}
unset($g);

// =======================================================================
// 3) Создаем уровни: категории, бренды, типы
// =======================================================================

$categories = []; // depth = 1
$brands = [];     // depth = 3
$types = [];      // depth >= 4

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
// 4) Привязываем товары к категории, бренду, типу
// =======================================================================

$resultProducts = [];

// Генерация виртуального типа
function getVirtualType() {
    return [
        "uuid" => "type-other",
        "name" => "Разное"
    ];
}

$virtualType = getVirtualType();
$types[$virtualType["uuid"]] = $virtualType; // добавляем в список типов

foreach ($products as $p) {

    if (empty($p["parentUuid"])) continue;

    $gid = $p["parentUuid"];

    // определяем всю цепочку родителей
    $chain = [];
    $current = $gid;

    while (!empty($current) && isset($groups[$current])) {
        $chain[] = $current;
        $current = $groups[$current]["parent"];
    }

    // сортируем цепочку по глубине
    usort($chain, function($a, $b) use ($groups) {
        return $groups[$a]["depth"] <=> $groups[$b]["depth"];
    });

    // Инициализация
    $cat = null;
    $brand = null;
    $type = null;

    foreach ($chain as $uuid) {
        $depth = $groups[$uuid]["depth"];

        if ($depth === 1) { 
            $cat = $uuid;
        }
        elseif ($depth === 3) {
            $brand = $uuid;
        }
        elseif ($depth >= 4) {
            $type = $uuid;
        }
    }

    // если товара нет типа → присваиваем "Разное"
    if (!$type) {
        $type = $virtualType["uuid"];
    }

    // готовим товар
    $resultProducts[] = [
        "uuid"      => $p["uuid"],
        "name"      => $p["name"],
        "price"     => $p["price"] ?? 0,
        "quantity"  => $p["quantity"] ?? 0,
        "barcode"   => $p["barCodes"][0] ?? "",
        "article"   => $p["articleNumber"] ?? "",
        "categoryUuid" => $cat,
        "brandUuid"    => $brand,
        "typeUuid"     => $type
    ];
}

// =======================================================================
// 5) Возвращаем результат
// =======================================================================

echo json_encode([
    "categories" => array_values($categories),
    "brands"     => array_values($brands),
    "types"      => array_values($types),
    "products"   => $resultProducts
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
