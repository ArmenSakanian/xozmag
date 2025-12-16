<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$product_id = intval($data["product_id"] ?? 0);
$attributes = $data["attributes"] ?? [];
$deleted    = $data["deleted"] ?? []; // 👈 ЯВНО удалённые (по крестику)

if ($product_id <= 0) {
    echo json_encode(["error" => "Неверный product_id"]);
    exit;
}

$pdo->beginTransaction();

try {

    /* === ЯВНОЕ УДАЛЕНИЕ === */
    if (!empty($deleted)) {
        $placeholders = implode(",", array_fill(0, count($deleted), "?"));

        $pdo->prepare("
            DELETE FROM product_attribute_values
            WHERE product_id = ?
              AND attribute_id IN ($placeholders)
        ")->execute(array_merge([$product_id], $deleted));
    }

    /* === INSERT / UPDATE === */
    foreach ($attributes as $row) {
        $attrId = intval($row["attribute_id"] ?? 0);
        $optId  = intval($row["option_id"] ?? 0);

        if ($attrId <= 0 || $optId <= 0) continue;

        $stmt = $pdo->prepare("
            SELECT id
            FROM product_attribute_values
            WHERE product_id = ? AND attribute_id = ?
        ");
        $stmt->execute([$product_id, $attrId]);

        if ($stmt->fetch()) {
            $pdo->prepare("
                UPDATE product_attribute_values
                SET option_id = ?, value = NULL
                WHERE product_id = ? AND attribute_id = ?
            ")->execute([$optId, $product_id, $attrId]);
        } else {
            $pdo->prepare("
                INSERT INTO product_attribute_values
                (product_id, attribute_id, option_id, value)
                VALUES (?, ?, ?, NULL)
            ")->execute([$product_id, $attrId, $optId]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}