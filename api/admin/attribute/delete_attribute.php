<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$attribute_id = intval($data["attribute_id"] ?? 0);

if ($attribute_id <= 0) {
    echo json_encode(["error" => "Некорректный attribute_id"]);
    exit;
}

$pdo->beginTransaction();

try {
    /* === проверяем, что характеристика существует === */
    $stmt = $pdo->prepare("
        SELECT id
        FROM product_attributes
        WHERE id = ?
    ");
    $stmt->execute([$attribute_id]);

    if (!$stmt->fetch()) {
        throw new Exception("Характеристика не найдена");
    }

    /* === 1️⃣ удаляем привязки к товарам === */
    $pdo->prepare("
        DELETE FROM product_attribute_values
        WHERE attribute_id = ?
    ")->execute([$attribute_id]);

    /* === 2️⃣ удаляем значения характеристики === */
    $pdo->prepare("
        DELETE FROM product_attribute_options
        WHERE attribute_id = ?
    ")->execute([$attribute_id]);

    /* === 3️⃣ удаляем сам заголовок === */
    $pdo->prepare("
        DELETE FROM product_attributes
        WHERE id = ?
    ")->execute([$attribute_id]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "deleted_attribute_id" => $attribute_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
