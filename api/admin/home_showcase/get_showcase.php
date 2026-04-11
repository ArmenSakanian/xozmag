<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
require_once __DIR__ . '/_common.php';

hs_json([
    'ok' => true,
    'settings' => hs_fetch_settings($pdo),
    'items' => hs_fetch_items($pdo, false),
]);
