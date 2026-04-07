<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
require_once __DIR__ . '/_common.php';

hs_ensure_schema($pdo);
$title = hs_clean_text($_POST['title'] ?? '', 255);
if ($title === null) hs_fail('Заполни название');

$description = hs_clean_text($_POST['description'] ?? '');
$price = hs_clean_price($_POST['price'] ?? '');
$buttonText = hs_clean_text($_POST['button_text'] ?? '', 255);
$buttonUrl = hs_normalize_url($_POST['button_url'] ?? '');
$imageUrl = hs_handle_upload('image');
$sortOrder = hs_next_sort_order($pdo);

$stmt = $pdo->prepare('INSERT INTO home_showcase_items (title, description, price, image_url, button_text, button_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, 1, ?)');
$stmt->execute([$title, $description, $price, $imageUrl, $buttonText, $buttonUrl, $sortOrder]);
$id = (int)$pdo->lastInsertId();

hs_json([
    'ok' => true,
    'item' => hs_fetch_item($pdo, $id),
]);
