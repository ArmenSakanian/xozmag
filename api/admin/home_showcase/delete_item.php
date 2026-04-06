<?php
header("Content-Type: application/json; charset=utf-8");
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/_common.php";

$id = (int)($_POST["id"] ?? 0);
if ($id <= 0) {
    $raw = file_get_contents("php://input");
    if ($raw !== false && $raw !== "") {
        $json = json_decode($raw, true);
        if (is_array($json)) $id = (int)($json["id"] ?? 0);
    }
}
if ($id <= 0) hs_json_fail("NO_ID", 422);

try {
    hs_ensure_schema($pdo);
    $sel = $pdo->prepare("SELECT id, image_url FROM home_showcase_items WHERE id = :id LIMIT 1");
    $sel->execute([":id" => $id]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$row) hs_json_fail("NOT_FOUND", 404);

    $del = $pdo->prepare("DELETE FROM home_showcase_items WHERE id = :id");
    $del->execute([":id" => $id]);

    hs_delete_image_by_url((string)$row["image_url"]);

    echo json_encode([
        "ok" => true,
        "deleted_id" => $id,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    hs_json_fail("DB_ERROR", 500);
}
