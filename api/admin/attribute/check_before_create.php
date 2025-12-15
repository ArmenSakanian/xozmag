<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* =========================
   helpers
========================= */
function normalizeValue($value) {
    $value = trim($value);
    $value = str_replace(",", ".", $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return $value;
}

$data = json_decode(file_get_contents("php://input"), true);

$nameRaw   = trim($data["name"] ?? "");
$valuesRaw = $data["values"] ?? [];

$nameCheck = mb_strtolower($nameRaw, 'UTF-8');

$result = [
    "attribute_exists"      => false,
    "attribute_id"          => null,
    "duplicate_values"      => [], // ❌ дубли для этого заголовка
    "values_used_elsewhere" => [], // ⚠️ warning
];

/* ===============================
   1️⃣ Проверка заголовка по NAME
=============================== */
if ($nameCheck !== "") {
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM product_attributes
        WHERE LOWER(name) = ?
    ");
    $stmt->execute([$nameCheck]);
    $attr = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($attr) {
        $result["attribute_exists"] = true;
        $result["attribute_id"] = $attr["id"];
    }
} else {
    $attr = null;
}

/* ===============================
   2️⃣ Проверка значений
=============================== */
$seen = [];

foreach ($valuesRaw as $vRaw) {
    if (!is_string($vRaw)) continue;

    $valueSave = normalizeValue($vRaw);
    if ($valueSave === "") continue;

    $valueCheck = mb_strtolower($valueSave, 'UTF-8');

    /* ❌ дубль в одном запросе */
    if (isset($seen[$valueCheck])) {
        $result["duplicate_values"][] = $valueSave;
        continue;
    }
    $seen[$valueCheck] = true;

    /* ❌ уже существует у ЭТОГО заголовка */
    if ($attr) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM product_attribute_options
            WHERE attribute_id = ?
              AND LOWER(value) = ?
        ");
        $stmt->execute([$attr["id"], $valueCheck]);

        if ($stmt->fetch()) {
            $result["duplicate_values"][] = $valueSave;
            continue;
        }
    }

    /* ⚠️ используется у других заголовков */
    $stmt = $pdo->prepare("
        SELECT pa.name
        FROM product_attribute_options o
        JOIN product_attributes pa ON pa.id = o.attribute_id
        WHERE LOWER(o.value) = ?
    ");
    $stmt->execute([$valueCheck]);
    $used = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($used) {
        $result["values_used_elsewhere"][] = [
            "value" => $valueSave,
            "attribute" => $used["name"]
        ];
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
