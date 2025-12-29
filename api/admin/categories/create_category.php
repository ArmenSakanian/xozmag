<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
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

/* ========= SLUG HELPERS ========= */
function slugify_ru($text) {
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
        'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
        'х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];

    $s = mb_strtolower(trim((string)$text), 'UTF-8');
    $s = strtr($s, $map);
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    $s = preg_replace('~-+~', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? $s : 'cat';
}

function slug_exists(PDO $pdo, $slug) {
    $st = $pdo->prepare("SELECT 1 FROM categories WHERE slug = ? LIMIT 1");
    $st->execute([$slug]);
    return (bool)$st->fetchColumn();
}

function make_unique_slug(PDO $pdo, $base) {
    $slug = $base;
    $i = 2;
    while (slug_exists($pdo, $slug)) {
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

/**
 * ✅ Разрешаем одинаковые названия только в разных ветках:
 * - НЕЛЬЗЯ: один и тот же parent_id + name
 * - МОЖНО: одинаковый name, если parent_id разный
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

    // ✅ slug для корня: просто от имени
    $slugBase = slugify_ru($name);
    $slug = make_unique_slug($pdo, $slugBase);

    $stmt = $pdo->prepare("
        INSERT INTO categories (parent_id, name, slug, sort, level, code)
        VALUES (NULL, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $slug, $nextSort, $level, $code]);

    echo json_encode([
        "success" => true,
        "id" => (int)$pdo->lastInsertId(),
        "slug" => $slug
    ]);
    exit;
}

// === ЕСЛИ parent_id УКАЗАН — ПОДКАТЕГОРИЯ ===

// получаем родителя (добавили slug)
$stmt = $pdo->prepare("
    SELECT id, sort, level, code, slug
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

// ✅ slug для подкатегории: parentSlug + "-" + slug(name)
$slugBase = ($parent["slug"] ? $parent["slug"] . "-" : "") . slugify_ru($name);
$slug = make_unique_slug($pdo, $slugBase);

// вставляем
$stmt = $pdo->prepare("
    INSERT INTO categories (parent_id, name, slug, sort, level, code)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$parent_id, $name, $slug, $nextSort, $level, $code]);

echo json_encode([
    "success" => true,
    "id" => (int)$pdo->lastInsertId(),
    "slug" => $slug
]);
