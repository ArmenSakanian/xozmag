<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

try {
    $stmt = $pdo->query("
SELECT 
    p.id,
    p.name,
    p.article,
    p.brand,
    p.price,
    p.barcode,
    CONCAT(c.level_code, ' — ', c.name) AS category_name
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
ORDER BY p.id DESC
");

    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
