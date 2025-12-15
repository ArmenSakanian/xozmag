<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$product_id = intval($data["product_id"] ?? 0);
$attributes = $data["attributes"] ?? [];

if ($product_id <= 0) {
    echo json_encode(["error" => "Неверный product_id"]);
    exit;
}

$pdo->beginTransaction();

try {

    /* ===============================
       ЕСЛИ НИЧЕГО НЕ ПЕРЕДАНО
       → УДАЛЯЕМ ВСЁ
    =============================== */
    if (empty($attributes)) {
        $pdo->prepare("
            DELETE FROM product_attribute_values
            WHERE product_id = ?
        ")->execute([$product_id]);

        $pdo->commit();
        echo json_encode(["success" => true]);
        exit;
    }

    /* ===============================
       Собираем attribute_id,
       которые ДОЛЖНЫ ОСТАТЬСЯ
    =============================== */
    $keepAttrIds = [];

    foreach ($attributes as $row) {
        $attrId = intval($row["attribute_id"] ?? 0);
        if ($attrId > 0) {
            $keepAttrIds[] = $attrId;
        }
    }

    if (!empty($keepAttrIds)) {
        $placeholders = implode(",", array_fill(0, count($keepAttrIds), "?"));

        $pdo->prepare("
            DELETE FROM product_attribute_values
            WHERE product_id = ?
              AND attribute_id NOT IN ($placeholders)
        ")->execute(array_merge([$product_id], $keepAttrIds));
    }

    /* ===============================
       UPDATE / INSERT
    =============================== */
    foreach ($attributes as $row) {
        $attribute_id = intval($row["attribute_id"] ?? 0);
        if ($attribute_id <= 0) continue;

        $option_id = intval($row["option_id"] ?? 0);

        if ($option_id <= 0) continue;

        $stmt = $pdo->prepare("
            SELECT id
            FROM product_attribute_values
            WHERE product_id = ?
              AND attribute_id = ?
        ");
        $stmt->execute([$product_id, $attribute_id]);

        if ($stmt->fetch()) {
            $pdo->prepare("
                UPDATE product_attribute_values
                SET option_id = ?, value = NULL
                WHERE product_id = ? AND attribute_id = ?
            ")->execute([$option_id, $product_id, $attribute_id]);
        } else {
            $pdo->prepare("
                INSERT INTO product_attribute_values
                (product_id, attribute_id, option_id, value)
                VALUES (?, ?, ?, NULL)
            ")->execute([$product_id, $attribute_id, $option_id]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
