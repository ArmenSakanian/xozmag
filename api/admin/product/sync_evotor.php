<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

// 1. Загружаем данные из evotor_catalog.php
$url = "https://xozmag.ru/api/vitrina/evotor_catalog.php";

$json = file_get_contents($url);
if (!$json) {
    echo json_encode(["error" => "Не удалось загрузить evotor_catalog"]);
    exit;
}

$data = json_decode($json, true);

if (!is_array($data) || empty($data["products"])) {
    echo json_encode(["error" => "Неверный формат данных"]);
    exit;
}

$products = $data["products"];

$inserted = 0;
$updated = 0;

// 2. Подготавливаем SQL
$sqlSelect = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
$sqlInsert = $pdo->prepare("
INSERT INTO products 
(name, article, brand, type, price, barcode, description, photo, quantity, category_id)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)

");

$sqlUpdate = $pdo->prepare("
UPDATE products SET 
    name = ?, 
    article = ?, 
    brand = ?, 
    type = ?,
    price = ?, 
    description = ?, 
    photo = ?, 
    quantity = ?
WHERE barcode = ?

");

foreach ($products as $p) {

    $name        = $p["name"] ?? "";
    $price       = $p["price"] ?? 0;
    $quantity    = $p["quantity"] ?? 0;
    $barcode     = $p["barcode"] ?? "";
    $article     = $p["article"] ?? "";
    $brand       = $p["brandName"] ?? "";
    $type        = $p["typeName"] ?? null;
    $description = $p["description"] ?? "";
    $images      = json_encode($p["images"] ?? [], JSON_UNESCAPED_UNICODE);

    if (!$barcode) continue;

    // Проверяем, есть ли уже товар с таким штрихкодом
    $sqlSelect->execute([$barcode]);
    $exists = $sqlSelect->fetchColumn();

    if ($exists) {
        // UPDATE
$sqlUpdate->execute([
  $name, $article, $brand, $type, $price, $description, $images, $quantity, $barcode
]);

        $updated++;

    } else {
        // INSERT
$sqlInsert->execute([
  $name, $article, $brand, $type, $price, $barcode, $description, $images, $quantity
]);

        $inserted++;
    }
}

echo json_encode([
    "success"  => true,
    "inserted" => $inserted,
    "updated"  => $updated
], JSON_UNESCAPED_UNICODE);
