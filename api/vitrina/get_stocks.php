<?php
require "config.php";

header("Content-Type: application/json");

$store = $_GET["store"] ?? "";

if (!$store) {
    echo json_encode(["error" => "storeUuid required"]);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$EVOTOR_API_URL/stores/$store/stocks");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $EVOTOR_TOKEN"
]);

echo curl_exec($ch);
curl_close($ch);
