<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../admin/home_showcase/_common.php';

hs_json([
    'ok' => true,
    'settings' => hs_fetch_settings($pdo),
    'items' => hs_fetch_items($pdo, true),
]);
