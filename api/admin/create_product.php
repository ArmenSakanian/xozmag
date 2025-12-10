<?php
require_once "../../config.php";

$data = json_decode(file_get_contents("php://input"), true);

$name        = $data["name"] ?? "";
$article     = $data["article"] ?? null;
$category_id = intval($data["category_id"] ?? 0);
$price       = floatval($data["price"] ?? 0);
$barcode     = $data["barcode"] ?? null;
$description = $data["description"] ?? null;

if (!$name || !$category_id) {
    echo json_encode(["error" => "Не хватает обязательных данных"]);
    exit;
}

$sql = "INSERT INTO products (name, article, category_id, price, barcode, description)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([$name, $article, $category_id, $price, $barcode, $description]);

echo json_encode(["success" => true, "product_id" => $pdo->lastInsertId()]);
