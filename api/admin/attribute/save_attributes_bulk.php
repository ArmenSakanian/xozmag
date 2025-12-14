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

        // ✅ ЕСЛИ attributes ПУСТО → УДАЛЯЕМ ВСЁ
        if (empty($attributes)) {
            $del = $pdo->prepare("
                DELETE FROM product_attribute_values
                WHERE product_id = ?
            ");
            $del->execute([$pid]);
            continue;
        }

        // ✅ ИНАЧЕ — ОБНОВЛЯЕМ / ДОБАВЛЯЕМ
        foreach ($attributes as $row) {
            $attrId = intval($row["attribute_id"]);
            $optId  = intval($row["option_id"]);

            if ($attrId <= 0 || $optId <= 0) continue;

            $stmt = $pdo->prepare("
                SELECT id FROM product_attribute_values
                WHERE product_id = ? AND attribute_id = ?
            ");
            $stmt->execute([$pid, $attrId]);

            if ($stmt->fetch()) {
                $upd = $pdo->prepare("
                    UPDATE product_attribute_values
                    SET option_id = ?, value = NULL
                    WHERE product_id = ? AND attribute_id = ?
                ");
                $upd->execute([$optId, $pid, $attrId]);
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO product_attribute_values
                    (product_id, attribute_id, option_id, value)
                    VALUES (?, ?, ?, NULL)
                ");
                $ins->execute([$pid, $attrId, $optId]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
