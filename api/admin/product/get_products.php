<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/*
   Возвращает ТОВАРЫ со всеми характеристиками
*/

$sql = "
SELECT 
    p.id,
    p.name,
    p.price,
    p.brand,
    p.type,
    p.barcode,
    p.description,


    c.name AS category_name,
    c.level_code AS category_code

FROM products p
LEFT JOIN categories c ON c.id = p.category_id
ORDER BY p.id DESC
";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== ХАРАКТЕРИСТИКИ =====

$sqlAttrs = "
SELECT 
    pav.product_id,
    pa.name AS attr_name,
    pav.value
FROM product_attribute_values pav
JOIN product_attributes pa ON pa.id = pav.attribute_id
ORDER BY pav.product_id
";

$stmt = $pdo->query($sqlAttrs);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$attrMap = [];

foreach ($rows as $r) {
    $pid = $r["product_id"];

    if (!isset($attrMap[$pid])) {
        $attrMap[$pid] = [];
    }

    $attrMap[$pid][] = [
        "name" => $r["attr_name"],
        "value" => $r["value"]
    ];
}


// ===============================
// НОРМАЛИЗАЦИЯ КАТЕГОРИЙ ТОВАРОВ
// ===============================

foreach ($products as &$p) {

    // --- нормализуем category_code ---
    $code = $p["category_code"];

    if ($code === null || $code === "") {
        // без категории
        $p["category_code"] = null;
        continue;
    }

    // если код НЕ начинается с точки — добавляем точку
    if (!str_starts_with($code, ".")) {
        $code = "." . $code;
    }

    $p["category_code"] = $code;

    // --- чистим category_name ---
    // если оно в формате ".1.2 — Арматура"
    if (str_contains($p["category_name"], "—")) {
        $parts = explode("—", $p["category_name"]);
        $p["category_name"] = trim($parts[1]);
    }
}


// ===============================
// ПРИКРЕПЛЯЕМ ХАРАКТЕРИСТИКИ
// ===============================

foreach ($products as &$p) {
    $id = $p["id"];
    $p["attributes"] = $attrMap[$id] ?? [];
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);
