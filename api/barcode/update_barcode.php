<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../db.php";

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid id"]);
    exit;
}

/* === ТЕКУЩАЯ ЗАПИСЬ === */
$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(["status" => "error", "msg" => "not found"]);
    exit;
}

/* === ДАННЫЕ === */
$name       = trim($_POST["product_name"] ?? "");
$sku        = trim($_POST["sku"] ?? "");
$contractor = trim($_POST["contractor"] ?? "");
$price      = trim($_POST["price"] ?? "");
$stock      = trim($_POST["stock"] ?? $item["stock"]);
$barcode    = trim($_POST["barcode"] ?? $item["barcode"]);

/* === НОРМАЛИЗАЦИЯ БАРКОДА: только цифры === */
$barcode = preg_replace('/\D+/', '', $barcode);
if ($barcode === "") $barcode = $item["barcode"];

$photo_url  = $item["photo"];

/* === 1) УДАЛЕНИЕ ФОТО === */
if (isset($_POST["remove_photo"])) {
    if (!empty($photo_url)) {
        $oldPath = $_SERVER["DOCUMENT_ROOT"] . $photo_url;
        if (file_exists($oldPath)) unlink($oldPath);

        // fallback: старые файлы могли лежать в /api
        $oldAlt = $_SERVER["DOCUMENT_ROOT"] . "/api" . $photo_url;
        if (file_exists($oldAlt)) unlink($oldAlt);
    }
    $photo_url = null;
}

/* === 2) НОВОЕ ФОТО === */
if (!empty($_FILES["photo"]["tmp_name"])) {

    $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg","jpeg","png","webp"];
    if (!in_array($ext, $allowed, true)) {
        echo json_encode(["status" => "error", "msg" => "Фото: разрешены JPG/JPEG/PNG/WEBP"]);
        exit;
    }

    // удалить старое
    if (!empty($photo_url)) {
        $oldPath = $_SERVER["DOCUMENT_ROOT"] . $photo_url;
        if (file_exists($oldPath)) unlink($oldPath);

        $oldAlt = $_SERVER["DOCUMENT_ROOT"] . "/api" . $photo_url;
        if (file_exists($oldAlt)) unlink($oldAlt);
    }

    $dir = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_barcode/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $fname = $barcode . "_" . time() . "." . $ext;
    $path  = $dir . $fname;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $path)) {
        $photo_url = "/photo_product_barcode/" . $fname;
    }
}

/* === 3) UPDATE === */
try {
    $stmt = $pdo->prepare("
        UPDATE barcodes
        SET barcode = ?, product_name = ?, sku = ?, contractor = ?, price = ?, stock = ?, photo = ?
        WHERE id = ?
    ");
    $stmt->execute([$barcode, $name, $sku, $contractor, $price, $stock, $photo_url, $id]);

} catch (PDOException $e) {
    if (!empty($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
        echo json_encode(["status" => "error", "msg" => "Штрихкод уже существует!"]);
        exit;
    }
    echo json_encode(["status" => "error", "msg" => "db error"]);
    exit;
}

/* === 4) ВОЗВРАТ === */
$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode([
    "status" => "success",
    "item"   => $stmt->fetch()
]);
