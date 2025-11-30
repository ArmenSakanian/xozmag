<?php
header('Content-Type: application/json');
require_once "db.php";

$code       = $_POST['barcode'] ?? null;
$name       = $_POST['product_name'] ?? null;
$sku        = $_POST['sku'] ?? null;
$contractor = $_POST['contractor'] ?? null;
$price      = $_POST['price'] ?? null;   // ← Важно добавлено

$photoPath = null;

/* === ОБРАБОТКА ФОТО === */
if (!empty($_FILES['photo']['tmp_name'])) {

    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $fileName = $code . "_" . time() . "." . $ext;

    $uploadPath = __DIR__ . '/../photo_product/' . $fileName;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
        $photoPath = '/photo_product/' . $fileName;
    }
}

if (!$code) {
    echo json_encode(["status" => "error", "message" => "Нет штрихкода"]);
    exit;
}

/* === ВСТАВКА В БД С ЦЕНОЙ === */
$stmt = $pdo->prepare("
    INSERT INTO barcodes (barcode, product_name, sku, contractor, price, photo, created_at)
    VALUES (:barcode, :name, :sku, :contractor, :price, :photo, NOW())
");

$stmt->execute([
    ":barcode"    => $code,
    ":name"       => $name,
    ":sku"        => $sku,
    ":contractor" => $contractor,
    ":price"      => $price,      // ← Важно добавлено
    ":photo"      => $photoPath
]);

echo json_encode([
    "status" => "success",
    "id"     => $pdo->lastInsertId(),
    "photo"  => $photoPath
]);