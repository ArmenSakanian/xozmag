<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = [];

$name = trim((string)($data["name"] ?? ""));
$parent_id = $data["parent_id"] ?? null;

// нормализуем parent_id
if ($parent_id === "" || $parent_id === false) {
    $parent_id = null;
}
if ($parent_id !== null) {
    $parent_id = intval($parent_id);
}

if ($name === "") {
    echo json_encode(["error" => "Название категории обязательно"]);
    exit;
}

/**
 * ✅ Разрешаем одинаковые названия только в разных ветках:
 * - НЕЛЬЗЯ: один и тот же parent_id + name
 * - МОЖНО: одинаковый name, если parent_id разный
 * null-safe сравнение parent_id делаем через <=> (работает и для NULL)
 */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM categories
    WHERE parent_id <=> ?
      AND LOWER(name) = LOWER(?)
");
$stmt->execute([$parent_id, $name]);

if ((int)$stmt->fetchColumn() > 0) {
    echo json_encode([
        "error" => "В этой группе уже есть категория с таким названием"
    ]);
    exit;
}

// === ЕСЛИ parent_id НЕ УКАЗАН — КОРНЕВАЯ КАТЕГОРИЯ ===
if ($parent_id === null) {

    // находим максимальный sort среди корневых
    $stmt = $pdo->query("
        SELECT COALESCE(MAX(sort), 0)
        FROM categories
        WHERE parent_id IS NULL
    ");
    $nextSort = (int)$stmt->fetchColumn() + 1;

    $level = 1;
    $code  = (string)$nextSort;

    $stmt = $pdo->prepare("
        INSERT INTO categories (parent_id, name, sort, level, code)
        VALUES (NULL, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $nextSort, $level, $code]);

    echo json_encode(["success" => true, "id" => (int)$pdo->lastInsertId()]);
    exit;
}

// === ЕСЛИ parent_id УКАЗАН — ПОДКАТЕГОРИЯ ===

// получаем родителя
$stmt = $pdo->prepare("
    SELECT id, sort, level, code
    FROM categories
    WHERE id = ?
");
$stmt->execute([$parent_id]);
$parent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    echo json_encode(["error" => "Родительская категория не найдена"]);
    exit;
}

// находим следующий sort среди детей родителя
$stmt = $pdo->prepare("
    SELECT COALESCE(MAX(sort), 0)
    FROM categories
    WHERE parent_id = ?
");
$stmt->execute([$parent_id]);
$nextSort = (int)$stmt->fetchColumn() + 1;

$level = (int)$parent["level"] + 1;
$code  = $parent["code"] . "." . $nextSort;

// вставляем
$stmt = $pdo->prepare("
    INSERT INTO categories (parent_id, name, sort, level, code)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$parent_id, $name, $nextSort, $level, $code]);

echo json_encode(["success" => true, "id" => (int)$pdo->lastInsertId()]);
