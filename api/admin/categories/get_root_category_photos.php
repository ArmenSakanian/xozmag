<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

function get_origin(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host !== '' ? ($scheme . '://' . $host) : '';
}

function map_category_row(array $row, string $origin): array {
    $file = trim((string)($row['photo_categories'] ?? ''));
    $url = $file !== '' ? '/photo_categories_vitrina/' . rawurlencode($file) : null;

    return [
        'id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'slug' => $row['slug'] ?? null,
        'code' => (string)$row['code'],
        'sort' => isset($row['sort']) ? (int)$row['sort'] : 0,
        'photo_categories' => $file !== '' ? $file : null,
        'photo_url' => $url ? $origin . $url . '?v=' . time() : null,
    ];
}

try {
    $origin = get_origin();

    try {
        $stmt = $pdo->query("
            SELECT id, name, slug, code, sort, photo_categories
            FROM categories
            WHERE level = 1 AND (parent_id IS NULL OR parent_id = 0)
            ORDER BY sort ASC, name ASC, id ASC
        ");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false || $e->getCode() === '42S22') {
            $stmt = $pdo->query("
                SELECT id, name, slug, code, sort, NULL AS photo_categories
                FROM categories
                WHERE level = 1 AND (parent_id IS NULL OR parent_id = 0)
                ORDER BY sort ASC, name ASC, id ASC
            ");
        } else {
            throw $e;
        }
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $items = array_map(static fn(array $row) => map_category_row($row, $origin), $rows);

    echo json_encode([
        'ok' => true,
        'items' => $items,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
