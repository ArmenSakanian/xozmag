<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name        = $data["name"] ?? "";
$article     = $data["article"] ?? null;
$category_id = intval($data["category_id"] ?? 0);
$price        = floatval($data["price"] ?? 0);
$barcode     = $data["barcode"] ?? null;
$description = $data["description"] ?? null;
$attributes  = $data["attributes"] ?? [];  // массив [{attribute_id, value}]

if (!$name || !$category_id) {
    echo json_encode(["error" => "Не хватает обязательных данных"]);
    exit;
}

// === 1. Создаём товар ===
$sql = "INSERT INTO products (name, article, category_id, price, barcode, description)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$name, $article, $category_id, $price, $barcode, $description]);

$product_id = $pdo->lastInsertId();

// === 2. Добавляем характеристики ===
$sqlAttr = "INSERT INTO product_attribute_values (product_id, attribute_id, value)
            VALUES (?, ?, ?)";
$stmtAttr = $pdo->prepare($sqlAttr);

foreach ($attributes as $attr) {
    if (!empty($attr["attribute_id"]) && !empty($attr["value"])) {
        $stmtAttr->execute([$product_id, $attr["attribute_id"], $attr["value"]]);
    }
}

echo json_encode(["success" => true, "product_id" => $product_id]);
