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
$nameRaw   = $data["name"] ?? "";
$slugRaw   = $data["slug"] ?? "";
$type      = $data["type"] ?? "select";
$uiRender  = $data["ui_render"] ?? "text";

/* =========================
   normalize
========================= */
$name = formatTitle($nameRaw);
$slug = trim($slugRaw);
$uiRender = trim((string)$uiRender);

/* =========================
   validation
========================= */
if ($name === "" || $slug === "") {
    echo json_encode(["error" => "Название или slug пустые"]);
    exit;
}

if (!in_array($type, ["select", "text", "number"], true)) {
    echo json_encode(["error" => "Неверный тип характеристики"]);
    exit;
}

$allowedUi = ["text", "color"];
if (!in_array($uiRender, $allowedUi, true)) {
    $uiRender = "text"; // мягко, чтобы не ломать старые клиенты
}

/* =========================
   ❌ name duplicate check (NO case sensitivity)
========================= */
$stmt = $pdo->prepare("
    SELECT id
    FROM product_attributes
    WHERE LOWER(name) = ?
");
$stmt->execute([mb_strtolower($name, 'UTF-8')]);

if ($stmt->fetch()) {
    echo json_encode([
        "error" => "Характеристика с таким названием уже существует"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   ❌ slug duplicate check (NO case sensitivity)
========================= */
$stmt = $pdo->prepare("
    SELECT id
    FROM product_attributes
    WHERE LOWER(slug) = ?
");
$stmt->execute([mb_strtolower($slug, 'UTF-8')]);

if ($stmt->fetch()) {
    echo json_encode([
        "error" => "Характеристика с таким slug уже существует"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   create
========================= */
$stmt = $pdo->prepare("
    INSERT INTO product_attributes (name, slug, type, ui_render)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$name, $slug, $type, $uiRender]);

echo json_encode([
    "success" => true,
    "id"        => $pdo->lastInsertId(),
    "name"      => $name,
    "slug"      => $slug,
    "type"      => $type,
    "ui_render" => $uiRender
], JSON_UNESCAPED_UNICODE);
