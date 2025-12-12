<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$product_id = intval($data["id"] ?? 0);
$attributes = $data["attributes"] ?? [];

if (!$product_id) {
  echo json_encode(["success" => false, "error" => "Нет product_id"]);
  exit;
}

$pdo->beginTransaction();

/* 1. Удаляем старые значения */
$pdo->prepare("
  DELETE FROM product_attribute_values
  WHERE product_id = ?
")->execute([$product_id]);

/* 2. Подготовка запросов */
$findAttr = $pdo->prepare("
  SELECT id FROM product_attributes WHERE name = ?
");

$insertAttr = $pdo->prepare("
  INSERT INTO product_attributes (name) VALUES (?)
");

$insertValue = $pdo->prepare("
  INSERT INTO product_attribute_values (product_id, attribute_id, value)
  VALUES (?, ?, ?)
");

/* 3. Обработка данных */
foreach ($attributes as $a) {

  $name  = trim($a["name"] ?? "");
  $value = trim($a["value"] ?? "");

  if ($name === "" || $value === "") {
    continue;
  }

  // ищем характеристику
  $findAttr->execute([$name]);
  $attrId = $findAttr->fetchColumn();

  // если нет — создаём
  if (!$attrId) {
    $insertAttr->execute([$name]);
    $attrId = $pdo->lastInsertId();
  }

  // сохраняем значение
  $insertValue->execute([$product_id, $attrId, $value]);
}

$pdo->commit();

echo json_encode(["success" => true]);
