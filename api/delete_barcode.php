<?php
header('Content-Type: application/json');
require_once __DIR__ . "/db.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid id"]);
    exit;
}

// Найдём запись, чтобы удалить фото
$stmt = $pdo->prepare("SELECT photo FROM barcodes WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(["status" => "error", "msg" => "not found"]);
    exit;
}

// Удаляем фото с диска
if (!empty($item["photo"])) {
    $path = $_SERVER["DOCUMENT_ROOT"] . $item["photo"];
    if (file_exists($path)) unlink($path);
}

// Удаляем строку
$stmt = $pdo->prepare("DELETE FROM barcodes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["status" => "success"]);
