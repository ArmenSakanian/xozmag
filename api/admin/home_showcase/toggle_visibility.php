<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
require_once __DIR__ . '/_common.php';

hs_ensure_schema($pdo);
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) hs_fail('Не передан id');
$current = hs_fetch_item($pdo, $id);
$next = (int)!((int)$current['is_active'] === 1);

$stmt = $pdo->prepare('UPDATE home_showcase_items SET is_active = ? WHERE id = ? LIMIT 1');
$stmt->execute([$next, $id]);

hs_json([
    'ok' => true,
    'item' => hs_fetch_item($pdo, $id),
]);
