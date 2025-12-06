<?php
require "config.php";

header("Content-Type: application/json");

$url = "https://api.evotor.ru/api/v1/inventories/stores/all/products";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $EVOTOR_TOKEN"
]);

$result = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$data = json_decode($result, true);

// Если пришёл массив товаров — ищем storeUuid
$found = null;
if (is_array($data)) {
    foreach ($data as $item) {
        if (isset($item['storeUuid'])) {
            $found = $item['storeUuid'];
            break;
        }
    }
}

echo json_encode([
    "http_code" => $http,
    "curl_error" => $error,
    "storeUuid" => $found,
    "sample_product" => isset($data[0]) ? $data[0] : null
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);