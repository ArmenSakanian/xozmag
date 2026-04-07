<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
require_once __DIR__ . '/_common.php';

hs_ensure_schema($pdo);
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) hs_fail('Не передан id');
$current = hs_fetch_item($pdo, $id);

$stmt = $pdo->prepare('DELETE FROM home_showcase_items WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
hs_delete_file($current['image_url'] ?? null);

hs_json(['ok' => true, 'id' => $id]);
