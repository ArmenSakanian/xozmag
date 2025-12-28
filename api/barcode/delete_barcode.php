<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../db.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid id"]);
    exit;
}

$stmt = $pdo->prepare("SELECT photo FROM barcodes WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(["status" => "error", "msg" => "not found"]);
    exit;
}

if (!empty($item["photo"])) {
    $photo = $item["photo"];

    // нормальный путь (корень)
    $path = $_SERVER["DOCUMENT_ROOT"] . $photo;
    if (file_exists($path)) unlink($path);

    // fallback: если старый файл лежит в /api
    $alt = $_SERVER["DOCUMENT_ROOT"] . "/api" . $photo;
    if (file_exists($alt)) unlink($alt);
}

$stmt = $pdo->prepare("DELETE FROM barcodes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["status" => "success"]);
