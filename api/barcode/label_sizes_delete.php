<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../db.php";

$id = intval($_GET["id"] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid id"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM barcode_label_sizes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["status" => "success"]);
