<?php
header("Content-Type: application/json");
require_once __DIR__ . "/db.php";

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid id"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(["status" => "error", "msg" => "not found"]);
    exit;
}

$name       = $_POST["product_name"] ?? "";
$sku        = $_POST["sku"] ?? "";
$contractor = $_POST["contractor"] ?? "";
$price      = $_POST["price"] ?? "";
$photo_url  = $item["photo"];

/* Удаление фотографии */
if (isset($_POST["remove_photo"])) {
    $photo_url = null;
}

/* Новое фото */
if (!empty($_FILES["photo"]["tmp_name"])) {

    $dir = __DIR__ . "/../uploads/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $fname = "photo_" . time() . "_" . rand(1000, 9999) . ".jpg";
    $path  = $dir . $fname;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $path)) {
        $photo_url = "/uploads/" . $fname;
    }
}

/* Обновление */
$stmt = $pdo->prepare("
    UPDATE barcodes
    SET product_name = ?, sku = ?, contractor = ?, price = ?, photo = ?
    WHERE id = ?
");

$stmt->execute([$name, $sku, $contractor, $price, $photo_url, $id]);

/* Обновлённая запись */
$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode([
    "status" => "success",
    "item"   => $stmt->fetch()
]);