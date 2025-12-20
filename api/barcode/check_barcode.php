<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../db.php";

$barcode = $_GET['barcode'] ?? '';

if ($barcode === '') {
    echo json_encode(["exists" => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM barcodes WHERE barcode = ?");
$stmt->execute([$barcode]);

$exists = $stmt->fetch() ? true : false;

echo json_encode(["exists" => $exists]);