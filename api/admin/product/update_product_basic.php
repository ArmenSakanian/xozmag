<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);

$hasName = array_key_exists("name", $data);
$hasCategory = array_key_exists("category_id", $data);

if (!$id || (!$hasName && !$hasCategory)) {
  echo json_encode(["success" => false, "error" => "Некорректные данные"]);
  exit;
}

$fields = [];
$params = [];

if ($hasName) {
  $fields[] = "name = ?";
  $params[] = trim($data["name"]);
}

if ($hasCategory) {
  $fields[] = "category_id = ?";
  $params[] = $data["category_id"] ? intval($data["category_id"]) : null;
}

$sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE id = ?";
$params[] = $id;

$pdo->prepare($sql)->execute($params);

echo json_encode(["success" => true]);
