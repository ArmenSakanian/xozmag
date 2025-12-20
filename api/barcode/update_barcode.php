<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../db.php";

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid id"]);
    exit;
}

/* === ПОЛУЧАЕМ ТЕКУЩУЮ ЗАПИСЬ === */
$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(["status" => "error", "msg" => "not found"]);
    exit;
}

/* === ДАННЫЕ ИЗ ФОРМЫ === */
$name       = $_POST["product_name"] ?? "";
$sku        = $_POST["sku"] ?? "";
$contractor = $_POST["contractor"] ?? "";
$price      = $_POST["price"] ?? "";
$stock      = $_POST["stock"] ?? $item["stock"];
$barcode    = $_POST["barcode"] ?? $item["barcode"];

$photo_url  = $item["photo"]; // текущее фото

/* === 1. УДАЛЕНИЕ ФОТО ЕСЛИ НАЖАТА КНОПКА "УДАЛИТЬ" === */
if (isset($_POST["remove_photo"])) {

    if (!empty($photo_url)) {
        $oldPath = $_SERVER["DOCUMENT_ROOT"] . $photo_url;
        if (file_exists($oldPath)) unlink($oldPath);
    }

    $photo_url = null;
}

/* === 2. НОВОЕ ФОТО === */
if (!empty($_FILES["photo"]["tmp_name"])) {

    // удалить старое фото
    if (!empty($photo_url)) {
        $oldPath = $_SERVER["DOCUMENT_ROOT"] . $photo_url;
        if (file_exists($oldPath)) unlink($oldPath);
    }

    // директория
    $dir = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_barcode/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    // новое имя файла — КАК ПРИ СОЗДАНИИ
    $ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
    $fname = $barcode . "_" . time() . "." . $ext;

    // путь
    $path = $dir . $fname;

    // сохраняем файл
    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $path)) {
        $photo_url = "/photo_product_barcode/" . $fname;
    }
}

/* === 3. ОБНОВЛЯЕМ В БАЗЕ === */
$stmt = $pdo->prepare("
    UPDATE barcodes
    SET barcode = ?, product_name = ?, sku = ?, contractor = ?, price = ?, stock = ?, photo = ?
    WHERE id = ?
");

$stmt->execute([$barcode, $name, $sku, $contractor, $price, $stock, $photo_url, $id]);

/* === 4. ВОЗВРАЩАЕМ ОБНОВЛЁННУЮ ЗАПИСЬ === */
$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode([
    "status" => "success",
    "item"   => $stmt->fetch()
]);
