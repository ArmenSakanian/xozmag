<?php
header("Content-Type: application/json; charset=utf-8");
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/_common.php";

$id = (int)($_POST["id"] ?? 0);
$isActive = isset($_POST["is_active"]) ? (int)$_POST["is_active"] : null;

if ($id <= 0 || $isActive === null) {
    $raw = file_get_contents("php://input");
    if ($raw !== false && $raw !== "") {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            if ($id <= 0) $id = (int)($json["id"] ?? 0);
            if ($isActive === null && array_key_exists("is_active", $json)) $isActive = (int)$json["is_active"];
        }
    }
}

if ($id <= 0) hs_json_fail("NO_ID", 422);
if ($isActive === null) hs_json_fail("NO_STATE", 422);
$isActive = $isActive === 1 ? 1 : 0;

try {
    hs_ensure_schema($pdo);

    $stmt = $pdo->prepare("UPDATE home_showcase_items SET is_active = :is_active WHERE id = :id");
    $stmt->execute([
        ":id" => $id,
        ":is_active" => $isActive,
    ]);

    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare("SELECT id FROM home_showcase_items WHERE id = :id LIMIT 1");
        $check->execute([":id" => $id]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) hs_json_fail("NOT_FOUND", 404);
    }

    $itemStmt = $pdo->prepare("SELECT id, title, description, price, image_url, button_text, button_url, is_active, sort_order, created_at, updated_at
        FROM home_showcase_items WHERE id = :id LIMIT 1");
    $itemStmt->execute([":id" => $id]);

    echo json_encode([
        "ok" => true,
        "item" => $itemStmt->fetch(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    hs_json_fail("DB_ERROR", 500);
}
