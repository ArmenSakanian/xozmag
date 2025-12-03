<?php
require "config.php";

header("Content-Type: application/json");

// === отладка ===
if (isset($_GET['debug'])) {
    echo json_encode([
        "debug" => [
            "token" => substr($EVOTOR_TOKEN, 0, 10) . "...(hidden)",
            "url" => "$EVOTOR_API_URL/stores"
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$EVOTOR_API_URL/stores");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $EVOTOR_TOKEN"
]);

$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode !== 200) {
    echo json_encode([
        "error" => "HTTP $httpcode",
        "message" => $result
    ]);
    exit;
}

echo $result;
