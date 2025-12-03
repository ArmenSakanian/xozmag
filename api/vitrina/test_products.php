<?php
require "config.php";

header("Content-Type: application/json");

$url = "https://api.evotor.ru/api/v1/inventories/products";

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

echo json_encode([
    "request_url" => $url,
    "http_code" => $http,
    "curl_error" => $error,
    "raw_result" => $result
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
