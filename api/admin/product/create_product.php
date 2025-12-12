<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$name        = $_POST["name"] ?? "";
$article     = $_POST["article"] ?? null;
$brand       = $_POST["brand"] ?? null;
$type        = $_POST["type"] ?? null;
$category_id = intval($_POST["category_id"] ?? 0);
$price       = floatval($_POST["price"] ?? 0);
$barcode     = $_POST["barcode"] ?? null;
$description = $_POST["description"] ?? null;

$attributes = json_decode($_POST["attributes"] ?? "[]", true);

if (!$name || !$category_id) {
    echo json_encode(["error" => "Не хватает данных"]);
    exit;
}

$sql = "
INSERT INTO products
(name, article, brand, type, category_id, price, barcode, description)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $name,
    $article,
    $brand,
    $type,
    $category_id,
    $price,
    $barcode,
    $description
]);

$product_id = $pdo->lastInsertId();

$sqlAttr = "
INSERT INTO product_attribute_values
(product_id, attribute_id, value)
VALUES (?, ?, ?)
";

$stmtAttr = $pdo->prepare($sqlAttr);

foreach ($attributes as $a) {
    if (!empty($a["attribute_id"]) && !empty($a["value"])) {
        $stmtAttr->execute([$product_id, $a["attribute_id"], $a["value"]]);
    }
}

echo json_encode([
    "success" => true,
    "product_id" => $product_id
]);
