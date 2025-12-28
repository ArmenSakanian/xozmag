<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$product_id = intval($_GET["product_id"] ?? 0);

if ($product_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        pav.attribute_id,
        pav.option_id,
        pa.name,
        o.value
    FROM product_attribute_values pav
    JOIN product_attributes pa ON pa.id = pav.attribute_id
    LEFT JOIN product_attribute_options o ON o.id = pav.option_id
    WHERE pav.product_id = ?
    ORDER BY pa.name
");
$stmt->execute([$product_id]);

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC),
    JSON_UNESCAPED_UNICODE
);