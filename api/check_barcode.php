<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/db.php";

$barcode = $_GET['barcode'] ?? '';

if ($barcode === '') {
    echo json_encode(["exists" => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM barcodes WHERE barcode = ?");
    $stmt->execute([$barcode]);
    $exists = $stmt->fetch() ? true : false;

    echo json_encode(["exists" => $exists]);
} catch (Throwable $e) {
    echo json_encode([
        "exists" => false,
        "error" => $e->getMessage()
    ]);
}