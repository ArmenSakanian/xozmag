<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* === helpers === */
function formatTitle($text) {
    $text = trim($text);
    if ($text === "") return $text;

    $first = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
    $rest  = mb_substr($text, 1, null, 'UTF-8');

    return $first . $rest;
}

$data = json_decode(file_get_contents("php://input"), true);

$product_id = intval($data["product_id"] ?? 0);
$attributes = $data["attributes"] ?? [];

if ($product_id <= 0) {
    echo json_encode(["error" => "Неверный product_id"]);
    exit;
}

$pdo->beginTransaction();

try {
    // Удаляем старые значения товара
    $del = $pdo->prepare("
        DELETE FROM product_attribute_values
        WHERE product_id = ?
    ");
    $del->execute([$product_id]);

    foreach ($attributes as $row) {
        $attribute_id = intval($row["attribute_id"] ?? 0);
        if ($attribute_id <= 0) continue;

        $option_id = intval($row["option_id"] ?? 0);
        $valueRaw  = $row["value"] ?? null;

        // === SELECT ===
        if ($option_id > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO product_attribute_values
                (product_id, attribute_id, option_id, value)
                VALUES (?, ?, ?, NULL)
            ");
            $stmt->execute([$product_id, $attribute_id, $option_id]);
            continue;
        }

        // === TEXT (на будущее, если понадобится) ===
        if ($valueRaw !== null) {
            $valueSave = formatTitle($valueRaw);

            if ($valueSave === "") continue;

            $stmt = $pdo->prepare("
                INSERT INTO product_attribute_values
                (product_id, attribute_id, option_id, value)
                VALUES (?, ?, NULL, ?)
            ");
            $stmt->execute([$product_id, $attribute_id, $valueSave]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
