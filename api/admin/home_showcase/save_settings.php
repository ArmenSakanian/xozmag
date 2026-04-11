<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
require_once __DIR__ . '/_common.php';

hs_ensure_schema($pdo);
$title = hs_clean_text($_POST['title'] ?? '', 255) ?? 'Подборка товаров';
$stmt = $pdo->prepare('UPDATE home_showcase_settings SET title = ? WHERE id = 1');
$stmt->execute([$title]);

hs_json([
    'ok' => true,
    'settings' => hs_fetch_settings($pdo),
]);
