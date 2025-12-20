<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../db.php";

/* === ПОЛУЧАЕМ ДАННЫЕ === */
$code       = trim($_POST['barcode'] ?? "");
$name       = trim($_POST['product_name'] ?? "");
$sku        = trim($_POST['sku'] ?? "");
$contractor = trim($_POST['contractor'] ?? "");
$price      = trim($_POST['price'] ?? "");
$stock      = trim($_POST['stock'] ?? ""); // ← НОВОЕ ПОЛЕ

/* === ВАЛИДАЦИЯ ПОЛЕЙ === */
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

/* === ФОТО === */
$photoPath = null;

if (!empty($_FILES['photo']['tmp_name'])) {

    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $fileName = $code . "_" . time() . "." . $ext;

    $uploadPath = __DIR__ . '/../photo_product_barcode/' . $fileName;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
        $photoPath = '/photo_product_barcode/' . $fileName;
    }
}

/* === ВСТАВКА В БАЗУ === */
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

    if ($e->errorInfo[1] == 1062) {
        echo json_encode([
            "status" => "error",
            "message" => "Штрихкод уже существует!"
        ]);
        exit;
    }

    echo json_encode([
        "status" => "error",
        "message" => "Ошибка базы данных"
    ]);
    exit;
}

/* === УСПЕХ === */
echo json_encode([
    "status" => "success",
    "id"     => $pdo->lastInsertId(),
    "photo"  => $photoPath
]);
