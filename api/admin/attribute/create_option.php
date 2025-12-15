<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* === helpers === */
function normalizeValue($value) {
    $value = trim($value);

    // заменить запятую на точку (для чисел)
    $value = str_replace(",", ".", $value);

    // убрать двойные пробелы
    $value = preg_replace('/\s+/', ' ', $value);

    return $value;
}

$data = json_decode(file_get_contents("php://input"), true);

$attribute_id = intval($data["attribute_id"] ?? 0);
$valueRaw     = $data["value"] ?? "";

/* === normalize === */
$valueSave  = normalizeValue($valueRaw);
$valueCheck = mb_strtolower($valueSave, 'UTF-8');

/* === validation === */
if ($attribute_id <= 0 || $valueCheck === "") {
    echo json_encode(["error" => "attribute_id или value пустые"]);
    exit;
}

/* === check attribute === */
$stmt = $pdo->prepare("SELECT type FROM product_attributes WHERE id = ?");
$stmt->execute([$attribute_id]);
$attr = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attr) {
    echo json_encode(["error" => "Характеристика не найдена"]);
    exit;
}

if ($attr["type"] !== "select") {
    echo json_encode(["error" => "Для этой характеристики нельзя добавлять варианты"]);
    exit;
}

/* === duplicate check (inside attribute only) === */
$check = $pdo->prepare("
    SELECT id
    FROM product_attribute_options
    WHERE attribute_id = ?
      AND LOWER(value) = ?
");
$check->execute([$attribute_id, $valueCheck]);

if ($check->fetch()) {
    echo json_encode(["error" => "Такое значение уже существует для этой характеристики"]);
    exit;
}

/* === create === */
$stmt = $pdo->prepare("
    INSERT INTO product_attribute_options (attribute_id, value)
    VALUES (?, ?)
");
$stmt->execute([$attribute_id, $valueSave]);

echo json_encode([
    "success" => true,
    "id" => $pdo->lastInsertId(),
    "attribute_id" => $attribute_id,
    "value" => $valueSave
], JSON_UNESCAPED_UNICODE);
