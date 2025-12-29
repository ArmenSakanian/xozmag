<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$category_id   = intval($data["id"] ?? 0);
$new_parent_id = $data["parent_id"] ?? null;

// нормализуем parent_id: пустая строка => NULL
if ($new_parent_id === "" || $new_parent_id === false) {
    $new_parent_id = null;
}

if ($category_id <= 0) {
    echo json_encode(["error" => "Неверный id категории"]);
    exit;
}

if ($new_parent_id !== null) {
    $new_parent_id = intval($new_parent_id);
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

function slug_exists_excluding(PDO $pdo, $slug, array $excludeIds = []) {
    if (empty($excludeIds)) {
        $st = $pdo->prepare("SELECT 1 FROM categories WHERE slug = ? LIMIT 1");
        $st->execute([$slug]);
        return (bool)$st->fetchColumn();
    }

    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $sql = "SELECT 1 FROM categories WHERE slug = ? AND id NOT IN ($placeholders) LIMIT 1";
    $st = $pdo->prepare($sql);
    $params = array_merge([$slug], array_values($excludeIds));
    $st->execute($params);
    return (bool)$st->fetchColumn();
}

function make_unique_slug(PDO $pdo, $base, array $excludeIds = []) {
    $slug = $base;
    $i = 2;
    while (slug_exists_excluding($pdo, $slug, $excludeIds)) {
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
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
    SELECT id, parent_id, code, slug, level
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

    // ✅ Запрещаем дубликаты в рамках одного родителя (но разрешаем одинаковые имена в разных ветках)
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM categories
        WHERE parent_id <=> ?
          AND LOWER(name) = LOWER(?)
          AND id <> ?
    ");
    $stmt->execute([$new_parent_id, $category["name"], $category_id]);
    if ((int)$stmt->fetchColumn() > 0) {
        throw new Exception("В выбранной группе уже есть категория с таким названием");
    }

    // 5️⃣ Новый sort / level / code + получаем slug родителя
    $parentSlug = null;

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
            SELECT code, level, slug
            FROM categories
            WHERE id = ?
        ");
        $stmt->execute([$new_parent_id]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$parent) {
            throw new Exception("Новый родитель не найден");
        }

        $parentSlug = $parent["slug"] ?? null;

        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(sort), 0)
            FROM categories
            WHERE parent_id = ?
        ");
        $stmt->execute([$new_parent_id]);
        $newSort = (int)$stmt->fetchColumn() + 1;

        $newLevel = (int)$parent["level"] + 1;
        $newCode  = $parent["code"] . "." . $newSort;
    }

    $oldCode  = $category["code"];
    $oldLevel = (int)$category["level"];
    $oldSlug  = (string)($category["slug"] ?? "");

    // ✅ новый slug для перемещаемой категории
    $selfBase = slugify_ru($category["name"]);
    $newSlugBase = ($parentSlug ? $parentSlug . "-" : "") . $selfBase;

    // исключаем из проверки себя и своих потомков (они тоже будут обновлены)
    $excludeIds = array_merge([$category_id], $descendants);
    $newSlug = make_unique_slug($pdo, $newSlugBase, $excludeIds);

    // 6️⃣ Обновляем саму категорию (добавили slug)
    $stmt = $pdo->prepare("
        UPDATE categories
        SET parent_id = ?, sort = ?, level = ?, code = ?, slug = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $new_parent_id,
        $newSort,
        $newLevel,
        $newCode,
        $newSlug,
        $category_id
    ]);

    // 7️⃣ Обновляем всех потомков (code/level/slug)
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
        $childNewLevel = (int)$child["level"] + $levelDiff;

        // пересчёт slug: заменяем префикс oldSlug на newSlug
        // работаем "по границе": либо конец строки, либо дальше "-"
        $childOldSlug = (string)($child["slug"] ?? "");
        $childNewSlug = $childOldSlug;

        if ($oldSlug !== "") {
            $childNewSlug = preg_replace(
                '/^' . preg_quote($oldSlug, '/') . '(?=-|$)/',
                $newSlug,
                $childOldSlug
            );
        }

        $stmt = $pdo->prepare("
            UPDATE categories
            SET code = ?, level = ?, slug = ?
            WHERE id = ?
        ");
        $stmt->execute([$childNewCode, $childNewLevel, $childNewSlug, $childId]);
    }

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
