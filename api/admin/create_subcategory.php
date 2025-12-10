<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name      = $data["name"] ?? null;
$levelCode = $data["level_code"] ?? null;

if (!$name || !$levelCode) {
    echo json_encode(["error" => "Не хватает данных"]);
    exit;
}

$parts = explode(".", $levelCode);

// родитель — это level_code без последней части
if (count($parts) == 1) {
    $parent_id = null; // основная категория
} else {
    $parent_code = implode(".", array_slice($parts, 0, -1));

    $stmt = $pdo->prepare("SELECT id FROM categories WHERE level_code = :code LIMIT 1");
    $stmt->execute([":code" => $parent_code]);
    $parent = $stmt->fetch();

    if (!$parent) {
        echo json_encode(["error" => "Родитель не найден"]);
        exit;
    }

    $parent_id = $parent["id"];
}

$level = count($parts);

$stmt = $pdo->prepare("
    INSERT INTO categories (name, parent_id, level, level_code, path)
    VALUES (:name, :parent_id, :level, :level_code, '')
");

$stmt->execute([
    ":name" => $name,
    ":parent_id" => $parent_id,
    ":level" => $level,
    ":level_code" => $levelCode
]);

$id = $pdo->lastInsertId();

// обновим path
if ($parent_id) {
    // получим path родителя
    $stmt = $pdo->prepare("SELECT path FROM categories WHERE id = :id");
    $stmt->execute([":id" => $parent_id]);
    $parentPath = $stmt->fetchColumn();
    $path = $parentPath . "/" . $id;
} else {
    $path = $id;
}

$pdo->prepare("UPDATE categories SET path = :path WHERE id = :id")
    ->execute([":path" => $path, ":id" => $id]);

echo json_encode(["success" => true, "id" => $id]);
