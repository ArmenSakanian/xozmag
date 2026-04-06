<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../admin/home_showcase/_common.php";

try {
    $settings = hs_get_settings($pdo);
    $items = hs_get_items($pdo, true);

    echo json_encode([
        "ok" => true,
        "title" => (string)($settings["block_title"] ?? ""),
        "items" => $items,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => "DB_ERROR",
    ], JSON_UNESCAPED_UNICODE);
}
