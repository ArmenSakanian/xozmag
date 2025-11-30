<?php
header('Content-Type: application/json');
require_once __DIR__ . "/db.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid id"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM barcodes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["status" => "success"]);