<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../db.php";

$barcode = $_GET['barcode'] ?? '';
$barcode = trim($barcode);

// Нормализуем как при сохранении: только цифры
$barcode = preg_replace('/\D+/', '', $barcode);

if ($barcode === '') {
    echo json_encode(["exists" => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM barcodes WHERE barcode = ? LIMIT 1");
$stmt->execute([$barcode]);

echo json_encode(["exists" => (bool)$stmt->fetch()]);
