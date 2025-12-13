<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$attribute_id = intval($_GET["attribute_id"] ?? 0);

if ($attribute_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, value
    FROM product_attribute_options
    WHERE attribute_id = ? AND is_active = 1
    ORDER BY sort, LOWER(value), value
");
$stmt->execute([$attribute_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
