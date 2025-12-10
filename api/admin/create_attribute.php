<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");

if ($name === "") {
    echo json_encode(["error" => "Название характеристики пустое"]);
    exit;
}

// Проверка на дубликат
$check = $pdo->prepare("SELECT id FROM product_attributes WHERE name = ?");
$check->execute([$name]);

if ($check->fetch()) {
    echo json_encode(["error" => "Такая характеристика уже существует"]);
    exit;
}

// Создаем
$sql = "INSERT INTO product_attributes (name) VALUES (?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$name]);

echo json_encode([
    "success" => true,
    "id" => $pdo->lastInsertId(),
    "name" => $name
], JSON_UNESCAPED_UNICODE);
