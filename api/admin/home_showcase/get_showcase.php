<?php
header("Content-Type: application/json; charset=utf-8");
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/_common.php";

try {
    echo json_encode([
        "ok" => true,
        "settings" => hs_get_settings($pdo),
        "items" => hs_get_items($pdo),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    hs_json_fail("DB_ERROR", 500);
}
