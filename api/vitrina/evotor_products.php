<?php
header("Content-Type: application/json; charset=utf-8");

// ---- Настройки ----
$token = "59a62817-90d7-4ee2-8a35-92d0de7ac91f"; // твой токен
$storeId = "20230324-1379-4034-80CD-1581DAED4A6E"; // твой storeUuid

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

if (!$response) {
    echo json_encode(["status" => "error", "message" => "No data"]);
    exit;
}

$products = json_decode($response, true);

$clean = [];

foreach ($products as $p) {

    // --- ФИЛЬТР: показывать только товары с остатком >= 1 ---
    if (!isset($p["quantity"]) || $p["quantity"] < 1) {
        continue; // пропускаем товар
    }

    $clean[] = [
        "uuid" => $p["uuid"] ?? "",
        "name" => $p["name"] ?? "",
        "price" => $p["price"] ?? 0,
        "quantity" => $p["quantity"] ?? 0,
        "description" => $p["description"] ?? "",
        "barcode" => $p["barCodes"][0] ?? "",
        "article" => $p["articleNumber"] ?? "",
    ];
}

echo json_encode($clean, JSON_UNESCAPED_UNICODE);
