<?php
header("Content-Type: application/json; charset=utf-8");
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/_common.php";

$title = trim((string)($_POST["block_title"] ?? ""));
if ($title === "") {
    $raw = file_get_contents("php://input");
    if ($raw !== false && $raw !== "") {
        $json = json_decode($raw, true);
        if (is_array($json)) $title = trim((string)($json["block_title"] ?? ""));
    }
}
if ($title === "") hs_json_fail("VALIDATION_ERROR", 422, ["field" => "block_title"]);
if (mb_strlen($title, "UTF-8") > 255) hs_json_fail("VALIDATION_ERROR", 422, ["field" => "block_title_too_long"]);

try {
    hs_ensure_schema($pdo);
    $stmt = $pdo->prepare("UPDATE home_showcase_settings SET block_title = :title WHERE id = 1");
    $stmt->execute([":title" => $title]);

    echo json_encode([
        "ok" => true,
        "settings" => hs_get_settings($pdo),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    hs_json_fail("DB_ERROR", 500);
}
