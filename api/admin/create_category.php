<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$name = $data["name"] ?? null;

if (!$name) {
    echo json_encode(["error" => "Название обязательно"]);
    exit;
}

// найти максимальный level_code среди Level 1
$stmt = $pdo->query("SELECT level_code FROM categories WHERE level = 1 ORDER BY level_code DESC LIMIT 1");
$row = $stmt->fetch();

if ($row) {
    $next = (int)$row["level_code"] + 1;
    $nextCode = (string)$next;
} else {
    $nextCode = "1";
}

$stmt = $pdo->prepare("
    INSERT INTO categories (name, parent_id, level, level_code, path)
    VALUES (:name, NULL, 1, :code, '')
");
$stmt->execute([
    ":name" => $name,
    ":code" => $nextCode
]);

$id = $pdo->lastInsertId();

// PATH = id
$pdo->prepare("UPDATE categories SET path = :p WHERE id = :id")
    ->execute([":p" => $id, ":id" => $id]);

echo json_encode(["success" => true, "id" => $id, "level_code" => $nextCode]);
