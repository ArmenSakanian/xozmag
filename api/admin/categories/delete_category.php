<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);

if ($id <= 0) {
    echo json_encode(["error" => "Неверный id категории"]);
    exit;
}

// 1️⃣ Проверяем, есть ли дочерние категории
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM categories
    WHERE parent_id = ?
");
$stmt->execute([$id]);

$childrenCount = (int)$stmt->fetchColumn();

if ($childrenCount > 0) {
    echo json_encode([
        "error" => "Нельзя удалить категорию: сначала удалите подкатегории"
    ]);
    exit;
}

// 2️⃣ Удаляем категорию
$stmt = $pdo->prepare("
    DELETE FROM categories
    WHERE id = ?
");
$stmt->execute([$id]);

echo json_encode(["success" => true]);
