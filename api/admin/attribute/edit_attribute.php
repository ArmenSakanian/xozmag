<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* =========================
   helpers
========================= */
function formatTitle($text) {
    $text = trim($text);
    if ($text === "") return "";

    $first = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
    $rest  = mb_substr($text, 1, null, 'UTF-8');
    return $first . $rest;
}

$data = json_decode(file_get_contents("php://input"), true);

/* =========================
   input
========================= */
$attributeId = intval($data["attribute_id"] ?? 0);
$nameRaw     = $data["name"] ?? "";
$slugRaw     = $data["slug"] ?? "";
$type        = $data["type"] ?? "select";
$valuesRaw   = $data["values"] ?? [];

if ($attributeId <= 0) {
    echo json_encode(["error" => "Некорректный attribute_id"]);
    exit;
}

$name = formatTitle($nameRaw);
$slug = trim($slugRaw);

if ($name === "" || $slug === "") {
    echo json_encode(["error" => "Название или slug пустые"]);
    exit;
}

/* =========================
   attribute exists
========================= */
$stmt = $pdo->prepare("SELECT id FROM product_attributes WHERE id = ?");
$stmt->execute([$attributeId]);
if (!$stmt->fetch()) {
    echo json_encode(["error" => "Характеристика не найдена"]);
    exit;
}

/* =========================
   ❌ name unique (except self)
========================= */
$stmt = $pdo->prepare("
    SELECT id FROM product_attributes
    WHERE LOWER(name) = ? AND id != ?
");
$stmt->execute([
    mb_strtolower($name, 'UTF-8'),
    $attributeId
]);

if ($stmt->fetch()) {
    echo json_encode([
        "error" => "Характеристика с таким названием уже существует"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   ❌ slug unique (except self)
========================= */
$stmt = $pdo->prepare("
    SELECT id FROM product_attributes
    WHERE LOWER(slug) = ? AND id != ?
");
$stmt->execute([
    mb_strtolower($slug, 'UTF-8'),
    $attributeId
]);

if ($stmt->fetch()) {
    echo json_encode(["error" => "Slug уже существует"]);
    exit;
}

$pdo->beginTransaction();

try {

    /* =========================
       update attribute
    ========================= */
    $pdo->prepare("
        UPDATE product_attributes
        SET name = ?, slug = ?, type = ?
        WHERE id = ?
    ")->execute([$name, $slug, $type, $attributeId]);

    /* =========================
       existing options (ID → value)
    ========================= */
    $stmt = $pdo->prepare("
        SELECT id, value
        FROM product_attribute_options
        WHERE attribute_id = ?
    ");
    $stmt->execute([$attributeId]);

    $existing = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing[$row["id"]] = $row["value"];
    }

    /* =========================
       normalize incoming
    ========================= */
    $incomingIds = [];
    $seenValues  = [];

    foreach ($valuesRaw as $row) {
        $optId    = isset($row["id"]) ? intval($row["id"]) : 0;
        $rawVal   = $row["value"] ?? "";
        $valSave  = formatTitle($rawVal);
        $valCheck = mb_strtolower($valSave, 'UTF-8');

        if ($valSave === "") continue;

        // ❌ duplicate in same request
        if (isset($seenValues[$valCheck])) {
            throw new Exception("Дублирующееся значение: {$valSave}");
        }
        $seenValues[$valCheck] = true;

        /* =========================
           UPDATE existing
        ========================= */
        if ($optId > 0 && isset($existing[$optId])) {

            if (mb_strtolower($existing[$optId], 'UTF-8') !== $valCheck) {

                $dup = $pdo->prepare("
                    SELECT id FROM product_attribute_options
                    WHERE attribute_id = ?
                      AND LOWER(value) = ?
                      AND id != ?
                ");
                $dup->execute([$attributeId, $valCheck, $optId]);

                if ($dup->fetch()) {
                    throw new Exception("Значение уже существует: {$valSave}");
                }

                $pdo->prepare("
                    UPDATE product_attribute_options
                    SET value = ?
                    WHERE id = ? AND attribute_id = ?
                ")->execute([$valSave, $optId, $attributeId]);
            }

            $incomingIds[] = $optId;
            continue;
        }

        /* =========================
           INSERT new
        ========================= */
        $dup = $pdo->prepare("
            SELECT id FROM product_attribute_options
            WHERE attribute_id = ?
              AND LOWER(value) = ?
        ");
        $dup->execute([$attributeId, $valCheck]);

        if ($dup->fetch()) {
            throw new Exception("Значение уже существует: {$valSave}");
        }

        $pdo->prepare("
            INSERT INTO product_attribute_options (attribute_id, value)
            VALUES (?, ?)
        ")->execute([$attributeId, $valSave]);

        $incomingIds[] = $pdo->lastInsertId();
    }

    /* =========================
       DELETE removed
    ========================= */
    if (!empty($incomingIds)) {
        $in = implode(",", array_map("intval", $incomingIds));

        $pdo->exec("
            DELETE FROM product_attribute_options
            WHERE attribute_id = {$attributeId}
              AND id NOT IN ({$in})
        ");
    } else {
        $pdo->prepare("
            DELETE FROM product_attribute_options
            WHERE attribute_id = ?
        ")->execute([$attributeId]);
    }

    $pdo->commit();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
