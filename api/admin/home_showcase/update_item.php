<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
require_once __DIR__ . '/_common.php';

hs_ensure_schema($pdo);
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) hs_fail('Не передан id');
$current = hs_fetch_item($pdo, $id);

$title = hs_clean_text($_POST['title'] ?? '', 255);
if ($title === null) hs_fail('Заполни название');

$description = hs_clean_text($_POST['description'] ?? '');
$price = hs_clean_price($_POST['price'] ?? '');
$buttonText = hs_clean_text($_POST['button_text'] ?? '', 255);
$buttonUrl = hs_normalize_url($_POST['button_url'] ?? '');
$imageUrl = $current['image_url'];
$replaceImage = !empty($_FILES['image']['tmp_name']);

if ($replaceImage) {
    $imageUrl = hs_handle_upload('image');
}

$stmt = $pdo->prepare('UPDATE home_showcase_items SET title = ?, description = ?, price = ?, image_url = ?, button_text = ?, button_url = ? WHERE id = ? LIMIT 1');
$stmt->execute([$title, $description, $price, $imageUrl, $buttonText, $buttonUrl, $id]);

if ($replaceImage && !empty($current['image_url'])) {
    hs_delete_file($current['image_url']);
}

hs_json([
    'ok' => true,
    'item' => hs_fetch_item($pdo, $id),
]);
