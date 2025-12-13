<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$nameRaw  = trim($data["name"] ?? "");
$valuesRaw = $data["values"] ?? [];

/* === нормализация для проверок === */
$nameCheck = mb_strtolower($nameRaw, 'UTF-8');

$result = [
  "attribute_exists" => false,
  "attribute_id" => null,
  "duplicate_values" => [],
  "values_used_elsewhere" => []
];

/* ===============================
   1️⃣ Проверяем заголовок (без регистра)
   =============================== */
$stmt = $pdo->prepare("
  SELECT id
  FROM product_attributes
  WHERE LOWER(name) = ?
");
$stmt->execute([$nameCheck]);
$attr = $stmt->fetch(PDO::FETCH_ASSOC);

if ($attr) {
  $result["attribute_exists"] = true;
  $result["attribute_id"] = $attr["id"];
}

/* ===============================
   2️⃣ Проверяем значения
   =============================== */
foreach ($valuesRaw as $vRaw) {
  $vRaw = trim($vRaw);
  if ($vRaw === "") continue;

  $vCheck = mb_strtolower($vRaw, 'UTF-8');

  /* --- есть ли такое значение у ЭТОГО заголовка --- */
  if ($attr) {
    $stmt = $pdo->prepare("
      SELECT id
      FROM product_attribute_options
      WHERE attribute_id = ?
        AND LOWER(value) = ?
    ");
    $stmt->execute([$attr["id"], $vCheck]);

    if ($stmt->fetch()) {
      $result["duplicate_values"][] = $vRaw; // возвращаем КРАСИВО
      continue;
    }
  }

  /* --- есть ли такое значение вообще (у другого заголовка) --- */
  $stmt = $pdo->prepare("
    SELECT pa.name
    FROM product_attribute_options o
    JOIN product_attributes pa ON pa.id = o.attribute_id
    WHERE LOWER(o.value) = ?
  ");
  $stmt->execute([$vCheck]);
  $used = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($used) {
    $result["values_used_elsewhere"][] = [
      "value" => $vRaw,           // как ввели
      "attribute" => $used["name"]
    ];
  }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
