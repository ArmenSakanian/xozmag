<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

// Данные приходят через multipart/form-data
$name        = $_POST["name"] ?? "";
$article     = $_POST["article"] ?? null;
$brand       = $_POST["brand"] ?? null;
$category_id = intval($_POST["category_id"] ?? 0);
$price       = floatval($_POST["price"] ?? 0);
$barcode     = $_POST["barcode"] ?? null;
$description = $_POST["description"] ?? null;

$attributes = json_decode($_POST["attributes"] ?? "[]", true);

// Проверка
if (!$name || !$category_id) {
    echo json_encode(["error" => "Не хватает данных"]);
    exit;
}

// ==== 1. Создаём товар ====

$sql = "INSERT INTO products 
(name, article, brand, category_id, price, barcode, description)
VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $name, 
    $article,
    $brand,
    $category_id, 
    $price, 
    $barcode,
    $description
]);

$product_id = $pdo->lastInsertId();

// ==== 2. Характеристики ====

$sqlAttr = "INSERT INTO product_attribute_values (product_id, attribute_id, value)
            VALUES (?, ?, ?)";
$stmtAttr = $pdo->prepare($sqlAttr);

foreach ($attributes as $attr) {
    if (!empty($attr["attribute_id"]) && !empty($attr["value"])) {
        $stmtAttr->execute([$product_id, $attr["attribute_id"], $attr["value"]]);
    }
}

echo json_encode(["success" => true, "product_id" => $product_id]);
