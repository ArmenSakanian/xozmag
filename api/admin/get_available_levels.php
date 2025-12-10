<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../db.php";

$root_id = $_GET["root_id"] ?? null;

if (!$root_id) {
    echo json_encode(["error" => "root_id required"]);
    exit;
}

// 1. Получаем код корневой категории
$stmt = $pdo->prepare("SELECT level_code FROM categories WHERE id = :id");
$stmt->execute([":id" => $root_id]);
$root = $stmt->fetch();

if (!$root) {
    echo json_encode(["error" => "root not found"]);
    exit;
}

$rootCode = $root["level_code"];

// 2. Получаем ВСЕ категории внутри этой группы
$stmt = $pdo->prepare("
    SELECT id, level_code 
    FROM categories
    WHERE level_code LIKE :code
    ORDER BY level_code ASC
");
$stmt->execute([":code" => $rootCode . "%"]);

$rows = $stmt->fetchAll();

// Группируем по уровням
$levels = [];

foreach ($rows as $r) {
    $depth = substr_count($r["level_code"], ".") + 1;
    $levels[$depth][] = $r["level_code"];
}

// -------- Генерация следующего уровня 2 --------

$level2 = isset($levels[2]) ? $levels[2] : [];

if (empty($level2)) {
    $possible_level2 = [$rootCode . ".1"];
} else {
    $last = end($level2);
    $parts = explode(".", $last);
    $next = (int)$parts[1] + 1;
    $possible_level2 = [$rootCode . "." . $next];
}

// -------- Генерация уровня 3 --------

$possible_level3 = [];

foreach ($level2 as $code) {
    $prefix = $code . ".";

    // ищем детей
    $stmt = $pdo->prepare("
        SELECT level_code FROM categories 
        WHERE level_code LIKE :prefix
        ORDER BY level_code DESC
        LIMIT 1
    ");
    $stmt->execute([":prefix" => $prefix . "%"]);
    $child = $stmt->fetch();

    if ($child) {
        $parts = explode(".", $child["level_code"]);
        $next = (int)end($parts) + 1;
        $possible_level3[] = $code . "." . $next;
    } else {
        $possible_level3[] = $code . ".1";
    }
}

echo json_encode([
    "level2" => $possible_level2,
    "level3" => $possible_level3,
], JSON_UNESCAPED_UNICODE);
