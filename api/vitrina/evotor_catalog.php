<?php
header("Content-Type: application/json; charset=utf-8");

$token = "59a62817-90d7-4ee2-8a35-92d0de7ac91f";
$storeId = "20230324-1379-4034-80CD-1581DAED4A6E";

$url = "https://api.evotor.ru/api/v1/inventories/stores/$storeId/products";

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

$groups = [];
$products = [];

// 1) Разделяем группы и товары
foreach ($data as $item) {
    if ($item["group"] === true) {
        $groups[$item["uuid"]] = [
            "uuid" => $item["uuid"],
            "name" => $item["name"],
            "parentUuid" => $item["parentUuid"] ?? null,
            "products" => []
        ];
    } else {
        $products[] = $item;
    }
}

// 2) НАХОДИМ ГЛАВНУЮ ГРУППУ ДЛЯ КАЖДОЙ ПОДГРУППЫ
function findTopGroup($id, $groups) {
    while ($groups[$id]["parentUuid"] !== null) {
        $id = $groups[$id]["parentUuid"];
    }
    return $id;
}

// 3) Привязываем товары к их ГЛАВНОЙ группе
foreach ($products as $p) {

    if (!isset($p["parentUuid"]) || !$p["parentUuid"]) {
        continue;
    }

    $gid = $p["parentUuid"];

    if (!isset($groups[$gid])) continue;

    // главная группа (без подгрупп)
    $top = findTopGroup($gid, $groups);

    $groups[$top]["products"][] = [
        "uuid" => $p["uuid"],
        "name" => $p["name"] ?? "",
        "price" => $p["price"] ?? 0,
        "quantity" => $p["quantity"] ?? 0,
        "barcode" => $p["barCodes"][0] ?? "",
        "article" => $p["articleNumber"] ?? ""
    ];
}

// 4) оставляем только главные группы
$result = [];

foreach ($groups as $g) {
    if ($g["parentUuid"] === null) { // только верхний уровень
        $result[] = $g;
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
