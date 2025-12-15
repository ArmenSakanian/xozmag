<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/*
  Возвращает ТОЛЬКО справочник характеристик
  (без привязки к товарам)
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        slug,
        type
    FROM product_attributes
    ORDER BY name
");

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC),
    JSON_UNESCAPED_UNICODE
);
