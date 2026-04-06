<?php
header("Content-Type: application/json; charset=utf-8");
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/_common.php";

$imageUrl = null;

try {
    hs_ensure_schema($pdo);
    $payload = hs_validate_item_payload($_POST);
    $imageUrl = hs_move_uploaded_image("image");

    $sortStmt = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM home_showcase_items");
    $sortOrder = (int)($sortStmt->fetch(PDO::FETCH_ASSOC)["max_sort"] ?? 0) + 1;

    $stmt = $pdo->prepare("INSERT INTO home_showcase_items
        (title, description, price, image_url, button_text, button_url, is_active, sort_order)
        VALUES (:title, :description, :price, :image_url, :button_text, :button_url, 1, :sort_order)");
    $stmt->execute([
        ":title" => $payload["title"],
        ":description" => ($payload["description"] !== "" ? $payload["description"] : null),
        ":price" => $payload["price"],
        ":image_url" => $imageUrl,
        ":button_text" => $payload["button_text"],
        ":button_url" => $payload["button_url"],
        ":sort_order" => $sortOrder,
    ]);

    $id = (int)$pdo->lastInsertId();
    $itemStmt = $pdo->prepare("SELECT id, title, description, price, image_url, button_text, button_url, is_active, sort_order, created_at, updated_at
        FROM home_showcase_items WHERE id = :id LIMIT 1");
    $itemStmt->execute([":id" => $id]);

    echo json_encode([
        "ok" => true,
        "item" => $itemStmt->fetch(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (!empty($imageUrl)) hs_delete_image_by_url($imageUrl);
    hs_json_fail("DB_ERROR", 500);
}
