<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$product_ids = $data["product_ids"] ?? [];
$attributes  = $data["attributes"] ?? [];

if (empty($product_ids)) {
    echo json_encode(["error" => "Нет товаров"]);
    exit;
}

$pdo->beginTransaction();

try {

    foreach ($product_ids as $pid) {
        $pid = intval($pid);
        if ($pid <= 0) continue;

        /* ===============================
           ЕСЛИ attributes ПУСТО
           → удаляем ВСЁ
        =============================== */
        if (empty($attributes)) {
            $pdo->prepare("
                DELETE FROM product_attribute_values
                WHERE product_id = ?
            ")->execute([$pid]);
            continue;
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

            $sql = "
                DELETE FROM product_attribute_values
                WHERE product_id = ?
                  AND attribute_id NOT IN ($placeholders)
            ";

            $pdo->prepare($sql)->execute(
                array_merge([$pid], $keepAttrIds)
            );
        }

        /* ===============================
           UPDATE / INSERT переданных
        =============================== */
        foreach ($attributes as $row) {
            $attrId = intval($row["attribute_id"] ?? 0);
            $optId  = intval($row["option_id"] ?? 0);

            if ($attrId <= 0 || $optId <= 0) continue;

            $stmt = $pdo->prepare("
                SELECT id
                FROM product_attribute_values
                WHERE product_id = ?
                  AND attribute_id = ?
            ");
            $stmt->execute([$pid, $attrId]);

            if ($stmt->fetch()) {
                $pdo->prepare("
                    UPDATE product_attribute_values
                    SET option_id = ?, value = NULL
                    WHERE product_id = ? AND attribute_id = ?
                ")->execute([$optId, $pid, $attrId]);
            } else {
                $pdo->prepare("
                    INSERT INTO product_attribute_values
                    (product_id, attribute_id, option_id, value)
                    VALUES (?, ?, ?, NULL)
                ")->execute([$pid, $attrId, $optId]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
