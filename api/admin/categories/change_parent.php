<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$category_id   = intval($data["id"] ?? 0);
$new_parent_id = $data["parent_id"] ?? null;

if ($category_id <= 0) {
    echo json_encode(["error" => "Неверный id категории"]);
    exit;
}

if ($new_parent_id !== null) {
    $new_parent_id = intval($new_parent_id);
}

// 1️⃣ Получаем категорию
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    echo json_encode(["error" => "Категория не найдена"]);
    exit;
}

// 2️⃣ Запрещаем parent = self
if ($new_parent_id === $category_id) {
    echo json_encode(["error" => "Категория не может быть родителем самой себя"]);
    exit;
}

// 3️⃣ Получаем всех потомков (чтобы запретить циклы)
$stmt = $pdo->query("
    SELECT id, parent_id, code
    FROM categories
");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

// строим карту детей
$childrenMap = [];
foreach ($all as $row) {
    if ($row["parent_id"] !== null) {
        $childrenMap[$row["parent_id"]][] = $row["id"];
    }
}

// рекурсивно собираем потомков
function collectDescendants($id, $map, &$out) {
    if (!isset($map[$id])) return;
    foreach ($map[$id] as $child) {
        $out[] = $child;
        collectDescendants($child, $map, $out);
    }
}

$descendants = [];
collectDescendants($category_id, $childrenMap, $descendants);

// 4️⃣ Запрещаем перенос в собственного потомка
if ($new_parent_id !== null && in_array($new_parent_id, $descendants)) {
    echo json_encode(["error" => "Нельзя переместить категорию внутрь её подкатегории"]);
    exit;
}

$pdo->beginTransaction();

try {

    // 5️⃣ Новый sort
    if ($new_parent_id === null) {
        $stmt = $pdo->query("
            SELECT COALESCE(MAX(sort), 0)
            FROM categories
            WHERE parent_id IS NULL
        ");
        $newSort = (int)$stmt->fetchColumn() + 1;
        $newLevel = 1;
        $newCode = (string)$newSort;
    } else {
        $stmt = $pdo->prepare("
            SELECT code, level
            FROM categories
            WHERE id = ?
        ");
        $stmt->execute([$new_parent_id]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$parent) {
            throw new Exception("Новый родитель не найден");
        }

        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(sort), 0)
            FROM categories
            WHERE parent_id = ?
        ");
        $stmt->execute([$new_parent_id]);
        $newSort = (int)$stmt->fetchColumn() + 1;

        $newLevel = $parent["level"] + 1;
        $newCode  = $parent["code"] . "." . $newSort;
    }

    $oldCode  = $category["code"];
    $oldLevel = $category["level"];

    // 6️⃣ Обновляем саму категорию
    $stmt = $pdo->prepare("
        UPDATE categories
        SET parent_id = ?, sort = ?, level = ?, code = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $new_parent_id,
        $newSort,
        $newLevel,
        $newCode,
        $category_id
    ]);

    // 7️⃣ Обновляем всех потомков
    foreach ($descendants as $childId) {

        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$childId]);
        $child = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$child) continue;

        // пересчёт code
        $childNewCode = preg_replace(
            '/^' . preg_quote($oldCode, '/') . '/',
            $newCode,
            $child["code"]
        );

        // пересчёт level
        $levelDiff = $newLevel - $oldLevel;
        $childNewLevel = $child["level"] + $levelDiff;

        $stmt = $pdo->prepare("
            UPDATE categories
            SET code = ?, level = ?
            WHERE id = ?
        ");
        $stmt->execute([$childNewCode, $childNewLevel, $childId]);
    }

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
