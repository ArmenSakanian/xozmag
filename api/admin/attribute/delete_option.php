<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$option_id = intval($data["option_id"] ?? 0);

if ($option_id <= 0) {
    echo json_encode(["error" => "Некорректный option_id"]);
    exit;
}

/* === проверяем, что значение существует === */
$stmt = $pdo->prepare("
    SELECT id
    FROM product_attribute_options
    WHERE id = ?
");
$stmt->execute([$option_id]);

if (!$stmt->fetch()) {
    echo json_encode(["error" => "Значение не найдено"]);
    exit;
}

$pdo->beginTransaction();

try {
    /* удаляем привязки к товарам */
    $pdo->prepare("
        DELETE FROM product_attribute_values
        WHERE option_id = ?
    ")->execute([$option_id]);

    /* удаляем вариант */
    $del = $pdo->prepare("
        DELETE FROM product_attribute_options
        WHERE id = ?
    ");
    $del->execute([$option_id]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "deleted_option_id" => $option_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
