<?php
header("Content-Type: application/json");

// Разрешаем CORS (если нужно)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Подключаем БД
require_once __DIR__ . '/db.php';

// Читаем JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Нет данных"]);
    exit;
}

// Проверка полей
$barcode       = $data['barcode']       ?? '';
$product_name  = $data['product_name']  ?? '';
$sku           = $data['sku']           ?? null;
$contractor    = $data['contractor']    ?? null;

// Проверка: название или артикул должны быть
if (empty($product_name) && empty($sku)) {
    echo json_encode([
        "success" => false,
        "message" => "Введите название товара или артикул"
    ]);
    exit;
}

// SQL вставка
$sql = "INSERT INTO barcodes (barcode, product_name, sku, contractor)
        VALUES (:barcode, :product_name, :sku, :contractor)";

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        ":barcode"      => $barcode,
        ":product_name" => $product_name,
        ":sku"          => $sku,
        ":contractor"   => $contractor
    ]);

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Ошибка при сохранении",
        "error"   => $e->getMessage()
    ]);
}
