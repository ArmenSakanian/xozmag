<?php
header("Content-Type: application/json; charset=utf-8");
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/_common.php";

$id = (int)($_POST["id"] ?? 0);
if ($id <= 0) hs_json_fail("NO_ID", 422);

$newImageUrl = null;

try {
    hs_ensure_schema($pdo);
    $payload = hs_validate_item_payload($_POST);

    $sel = $pdo->prepare("SELECT id, image_url FROM home_showcase_items WHERE id = :id LIMIT 1");
    $sel->execute([":id" => $id]);
    $current = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$current) hs_json_fail("NOT_FOUND", 404);

    $imageUrl = (string)$current["image_url"];
    if (!empty($_FILES["image"]) && is_array($_FILES["image"]) && (int)($_FILES["image"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $newImageUrl = hs_move_uploaded_image("image");
        $imageUrl = $newImageUrl;
    }

    $stmt = $pdo->prepare("UPDATE home_showcase_items
        SET title = :title,
            description = :description,
            price = :price,
            image_url = :image_url,
            button_text = :button_text,
            button_url = :button_url
        WHERE id = :id");
    $stmt->execute([
        ":id" => $id,
        ":title" => $payload["title"],
        ":description" => ($payload["description"] !== "" ? $payload["description"] : null),
        ":price" => $payload["price"],
        ":image_url" => $imageUrl,
        ":button_text" => $payload["button_text"],
        ":button_url" => $payload["button_url"],
    ]);

    if ($newImageUrl !== null && !empty($current["image_url"])) {
        hs_delete_image_by_url((string)$current["image_url"]);
    }

    $itemStmt = $pdo->prepare("SELECT id, title, description, price, image_url, button_text, button_url, is_active, sort_order, created_at, updated_at
        FROM home_showcase_items WHERE id = :id LIMIT 1");
    $itemStmt->execute([":id" => $id]);

    echo json_encode([
        "ok" => true,
        "item" => $itemStmt->fetch(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($newImageUrl !== null) hs_delete_image_by_url($newImageUrl);
    hs_json_fail("DB_ERROR", 500);
}
