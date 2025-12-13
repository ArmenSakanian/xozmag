<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$product_id = intval($_GET["product_id"] ?? 0);

/* ============================
   ЕСЛИ product_id ПЕРЕДАН
   → характеристики товара
============================ */
if ($product_id > 0) {
    $stmt = $pdo->prepare("
        SELECT
            a.id AS attribute_id,
            a.name,
            pav.option_id,
            COALESCE(o.value, pav.value) AS value
        FROM product_attribute_values pav
        JOIN product_attributes a ON a.id = pav.attribute_id
        LEFT JOIN product_attribute_options o ON o.id = pav.option_id
        WHERE pav.product_id = ?
        ORDER BY a.name
    ");
    $stmt->execute([$product_id]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

/* ============================
   ЕСЛИ product_id НЕТ
   → справочник характеристик
============================ */
$stmt = $pdo->query("
    SELECT id, name
    FROM product_attributes
    ORDER BY name
");

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC),
    JSON_UNESCAPED_UNICODE
);
