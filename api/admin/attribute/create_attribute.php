<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* === helpers === */
function formatTitle($text) {
    $text = trim($text);
    if ($text === "") return $text;

    $first = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
    $rest  = mb_substr($text, 1, null, 'UTF-8');

    return $first . $rest;
}

$data = json_decode(file_get_contents("php://input"), true);

$nameRaw = $data["name"] ?? "";
$slugRaw = $data["slug"] ?? "";
$type    = $data["type"] ?? "select";

/* === normalize === */
$name = formatTitle($nameRaw);
$slug = trim($slugRaw);

/* === validation === */
if ($name === "" || $slug === "") {
    echo json_encode(["error" => "Название или slug пустые"]);
    exit;
}

if (!in_array($type, ["select", "text", "number"])) {
    echo json_encode(["error" => "Неверный тип характеристики"]);
    exit;
}

/* === slug check (NO case sensitivity) === */
$check = $pdo->prepare("
    SELECT id
    FROM product_attributes
    WHERE LOWER(slug) = ?
");
$check->execute([mb_strtolower($slug, 'UTF-8')]);

if ($check->fetch()) {
    echo json_encode(["error" => "Характеристика с таким slug уже существует"]);
    exit;
}

/* === create === */
$stmt = $pdo->prepare("
    INSERT INTO product_attributes (name, slug, type)
    VALUES (?, ?, ?)
");
$stmt->execute([$name, $slug, $type]);

echo json_encode([
    "success" => true,
    "id"   => $pdo->lastInsertId(),
    "name" => $name,
    "slug" => $slug,
    "type" => $type
], JSON_UNESCAPED_UNICODE);
