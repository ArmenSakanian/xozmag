<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "error" => true,
        "message" => "Нет данных"
    ]);
    exit;
}

try {

    /* =====================================================
     | 1. МАССОВОЕ ОБНОВЛЕНИЕ
     ===================================================== */
    if (!empty($data["product_ids"]) && is_array($data["product_ids"])) {

        $categoryId = array_key_exists("category_id", $data)
            ? $data["category_id"]
            : null;

        $ids = array_map("intval", $data["product_ids"]);

        if (empty($ids)) {
            throw new Exception("Пустой список товаров");
        }

        // placeholders (?, ?, ?)
        $placeholders = implode(",", array_fill(0, count($ids), "?"));

        $sql = "
            UPDATE products
            SET category_id = ?
            WHERE id IN ($placeholders)
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute(array_merge(
            [$categoryId],
            $ids
        ));

        echo json_encode([
            "success" => true,
            "updated" => count($ids)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* =====================================================
     | 2. ОДИН ТОВАР
     ===================================================== */
    if (!empty($data["product_id"])) {

        $productId  = (int)$data["product_id"];
        $categoryId = array_key_exists("category_id", $data)
            ? $data["category_id"]
            : null;

        $stmt = $pdo->prepare("
            UPDATE products
            SET category_id = ?
            WHERE id = ?
        ");

        $stmt->execute([$categoryId, $productId]);

        echo json_encode([
            "success" => true,
            "product_id" => $productId
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception("Неверные параметры");

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
