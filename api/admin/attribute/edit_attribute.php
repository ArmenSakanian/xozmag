<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
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

function normalizeUiRender($v) {
    $v = trim((string)$v);
    $allowed = ["text", "color"];
    return in_array($v, $allowed, true) ? $v : "text";
}

function metaToJson($meta) {
    if (!is_array($meta) || empty($meta)) return null;
    return json_encode($meta, JSON_UNESCAPED_UNICODE);
}

$data = json_decode(file_get_contents("php://input"), true);

/* =========================
   input
========================= */
$attributeId = intval($data["attribute_id"] ?? 0);
$nameRaw     = $data["name"] ?? "";
$slugRaw     = $data["slug"] ?? "";
$type        = $data["type"] ?? "select";
$uiRender    = normalizeUiRender($data["ui_render"] ?? "text");
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
        SET name = ?, slug = ?, type = ?, ui_render = ?
        WHERE id = ?
    ")->execute([$name, $slug, $type, $uiRender, $attributeId]);

    /* =========================
       existing options (ID → value/meta)
    ========================= */
    $stmt = $pdo->prepare("
        SELECT id, value, meta_json
        FROM product_attribute_options
        WHERE attribute_id = ?
    ");
    $stmt->execute([$attributeId]);

    $existing = []; // id => ['value'=>..., 'meta_json'=>...]
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing[(int)$row["id"]] = [
            "value" => (string)$row["value"],
            "meta_json" => $row["meta_json"] ?? null
        ];
    }

    /* =========================
       normalize incoming
    ========================= */
    $incomingIds = [];
    $seenValues  = [];

    foreach ($valuesRaw as $row) {
        if (!is_array($row)) continue;

        $optId    = isset($row["id"]) ? intval($row["id"]) : 0;
        $rawVal   = $row["value"] ?? "";
        $valSave  = formatTitle($rawVal);
        $valCheck = mb_strtolower($valSave, 'UTF-8');

        if ($valSave === "") continue;

        // meta
        $metaArr = null;
        if (isset($row["meta"]) && is_array($row["meta"])) {
            $metaArr = $row["meta"];
        }
        $newMetaJson = metaToJson($metaArr);

        // ❌ duplicate in same request
        if (isset($seenValues[$valCheck])) {
            throw new Exception("Дублирующееся значение: {$valSave}");
        }
        $seenValues[$valCheck] = true;

        /* =========================
           UPDATE existing
        ========================= */
        if ($optId > 0 && isset($existing[$optId])) {

            $oldValCheck = mb_strtolower($existing[$optId]["value"], 'UTF-8');
            $oldMetaJson = $existing[$optId]["meta_json"] ?? null;

            // value changed?
            if ($oldValCheck !== $valCheck) {

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

            // meta changed?
            if (($oldMetaJson ?? null) !== ($newMetaJson ?? null)) {
                $pdo->prepare("
                    UPDATE product_attribute_options
                    SET meta_json = ?
                    WHERE id = ? AND attribute_id = ?
                ")->execute([$newMetaJson, $optId, $attributeId]);
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
            INSERT INTO product_attribute_options (attribute_id, value, meta_json)
            VALUES (?, ?, ?)
        ")->execute([$attributeId, $valSave, $newMetaJson]);

        $incomingIds[] = (int)$pdo->lastInsertId();
    }

    $incomingIds = array_values(array_unique(array_map("intval", $incomingIds)));

    /* =========================
       DELETE removed (и чистим привязки товаров)
    ========================= */
    $existingIds = array_map("intval", array_keys($existing));
    $toDelete = array_values(array_diff($existingIds, $incomingIds));

    if (!empty($toDelete)) {
        $in = implode(",", array_map("intval", $toDelete));

        // чистим привязки у товаров (чтобы не было "битых" option_id)
        $pdo->exec("
            DELETE FROM product_attribute_values
            WHERE option_id IN ({$in})
        ");

        // удаляем сами варианты
        $pdo->exec("
            DELETE FROM product_attribute_options
            WHERE attribute_id = {$attributeId}
              AND id IN ({$in})
        ");
    }

    $pdo->commit();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
