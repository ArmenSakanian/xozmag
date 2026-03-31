<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../search/product_finder.php';

$qRaw = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
if ($qRaw === '') {
    echo json_encode([]);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$catRaw = isset($_GET['cat']) ? trim((string)$_GET['cat']) : '';

try {
    $rows = pf_search_products_general($pdo, $qRaw, $limit, $catRaw);
    $rows = pf_map_rows_for_ui($rows);
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
