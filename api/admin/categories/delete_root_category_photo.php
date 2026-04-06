<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

function json_error(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function get_origin(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host !== '' ? ($scheme . '://' . $host) : '';
}

function fetch_root_category(PDO $pdo, int $id): array {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, slug, code, sort, level, parent_id, photo_categories
            FROM categories
            WHERE id = ? AND level = 1 AND (parent_id IS NULL OR parent_id = 0)
            LIMIT 1
        ");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false || $e->getCode() === '42S22') {
            json_error('В таблице categories нет поля photo_categories', 500);
        }
        throw $e;
    }

    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        json_error('Категория первого уровня не найдена', 404);
    }

    return $row;
}

function build_item(array $row): array {
    $origin = get_origin();
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

function remove_old_file(?string $filename): void {
    $filename = trim((string)$filename);
    if ($filename === '') {
        return;
    }

    $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/photo_categories_vitrina/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_error('Не передан id категории');
}

try {
    $category = fetch_root_category($pdo, $id);

    $stmt = $pdo->prepare("UPDATE categories SET photo_categories = NULL WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);

    remove_old_file($category['photo_categories'] ?? null);

    $updated = fetch_root_category($pdo, $id);

    echo json_encode([
        'ok' => true,
        'item' => build_item($updated),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    json_error($e->getMessage(), 500);
}
