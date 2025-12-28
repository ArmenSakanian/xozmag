<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../db.php";

/* === ДАННЫЕ === */
$code       = trim($_POST['barcode'] ?? "");
$name       = trim($_POST['product_name'] ?? "");
$sku        = trim($_POST['sku'] ?? "");
$contractor = trim($_POST['contractor'] ?? "");
$price      = trim($_POST['price'] ?? "");
$stock      = trim($_POST['stock'] ?? "");

/* === НОРМАЛИЗАЦИЯ БАРКОДА: только цифры === */
$code = preg_replace('/\D+/', '', $code);

/* === ВАЛИДАЦИЯ === */
if (!$code) {
    echo json_encode(["status" => "error", "message" => "Введите штрихкод"]);
    exit;
}

if (mb_strlen($name) < 2 && mb_strlen($sku) < 2 && mb_strlen($contractor) < 2) {
    echo json_encode([
        "status" => "error",
        "message" => "Заполните хотя бы одно поле: название, артикул или контрагент (минимум 2 символа)"
    ]);
    exit;
}

/* === ФОТО (в корень сайта /photo_product_barcode/) === */
$photoPath = null;

if (!empty($_FILES['photo']['tmp_name'])) {

    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ["jpg","jpeg","png","webp"];

    if (!in_array($ext, $allowed, true)) {
        echo json_encode(["status" => "error", "message" => "Фото: разрешены JPG/JPEG/PNG/WEBP"]);
        exit;
    }

    $dir = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_barcode/";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $fileName = $code . "_" . time() . "." . $ext;
    $uploadPath = $dir . $fileName;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
        $photoPath = "/photo_product_barcode/" . $fileName;
    }
}

/* === ВСТАВКА === */
try {
    $stmt = $pdo->prepare("
        INSERT INTO barcodes (barcode, product_name, sku, contractor, price, stock, photo, created_at)
        VALUES (:barcode, :name, :sku, :contractor, :price, :stock, :photo, NOW())
    ");

    $stmt->execute([
        ":barcode"    => $code,
        ":name"       => $name,
        ":sku"        => $sku,
        ":contractor" => $contractor,
        ":price"      => $price,
        ":stock"      => $stock,
        ":photo"      => $photoPath
    ]);

} catch (PDOException $e) {
    if (!empty($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
        echo json_encode(["status" => "error", "message" => "Штрихкод уже существует!"]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Ошибка базы данных"]);
    exit;
}

echo json_encode([
    "status" => "success",
    "id"     => $pdo->lastInsertId(),
    "photo"  => $photoPath
]);
