<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db.php';

function hs_fail(string $message, int $status = 400, array $extra = []): void {
    http_response_code($status);
    echo json_encode(array_merge(['ok' => false, 'error' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function hs_json(array $payload): void {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function hs_document_root(): string {
    $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    if ($root !== '') return $root;
    return dirname(__DIR__, 3);
}

function hs_upload_dir(): string {
    return hs_document_root() . '/home_showcase_cards';
}

function hs_public_url(string $path): string {
    $path = '/' . ltrim($path, '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') return $path;
    return ($https ? 'https://' : 'http://') . $host . $path;
}

function hs_table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function hs_column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetchColumn();
}

function hs_ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS home_showcase_settings (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS home_showcase_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        price VARCHAR(64) NULL,
        image_url VARCHAR(255) NOT NULL,
        button_text VARCHAR(255) NULL,
        button_url VARCHAR(500) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (!hs_column_exists($pdo, 'home_showcase_items', 'is_active')) {
        $pdo->exec("ALTER TABLE home_showcase_items ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER button_url");
    }
    if (!hs_column_exists($pdo, 'home_showcase_items', 'sort_order')) {
        $pdo->exec("ALTER TABLE home_showcase_items ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_active");
    }
    if (hs_column_exists($pdo, 'home_showcase_items', 'price')) {
        try {
            $pdo->exec("ALTER TABLE home_showcase_items MODIFY COLUMN price VARCHAR(64) NULL DEFAULT NULL");
        } catch (Throwable $e) {
        }
    }
    if (hs_column_exists($pdo, 'home_showcase_items', 'button_text')) {
        try {
            $pdo->exec("ALTER TABLE home_showcase_items MODIFY COLUMN button_text VARCHAR(255) NULL DEFAULT NULL");
        } catch (Throwable $e) {
        }
    }
    if (hs_column_exists($pdo, 'home_showcase_items', 'button_url')) {
        try {
            $pdo->exec("ALTER TABLE home_showcase_items MODIFY COLUMN button_url VARCHAR(500) NULL DEFAULT NULL");
        } catch (Throwable $e) {
        }
    }

    $stmt = $pdo->prepare('INSERT INTO home_showcase_settings (id, title) VALUES (1, ?) ON DUPLICATE KEY UPDATE id = id');
    $stmt->execute(['Подборка товаров']);
}

function hs_clean_text(?string $value, int $maxLen = 0): ?string {
    $value = trim((string)$value);
    if ($value === '') return null;
    if ($maxLen > 0) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return $value;
}

function hs_clean_price(?string $value): ?string {
    $value = preg_replace('/\D+/u', '', (string)$value);
    return $value !== '' ? $value : null;
}

function hs_normalize_url(?string $value): ?string {
    $value = trim((string)$value);
    if ($value === '') return null;
    if (preg_match('~^https?://~i', $value) || str_starts_with($value, '/') || str_starts_with($value, '#')) {
        return mb_substr($value, 0, 500);
    }
    return 'https://' . mb_substr($value, 0, 492);
}

function hs_fetch_settings(PDO $pdo): array {
    hs_ensure_schema($pdo);
    $stmt = $pdo->query('SELECT id, title, updated_at FROM home_showcase_settings WHERE id = 1 LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => 1, 'title' => 'Подборка товаров'];
    return [
        'id' => 1,
        'title' => (string)($row['title'] ?? 'Подборка товаров'),
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

function hs_map_item(array $row): array {
    return [
        'id' => (int)$row['id'],
        'title' => (string)$row['title'],
        'description' => $row['description'] !== null ? (string)$row['description'] : '',
        'price' => $row['price'] !== null ? preg_replace('/\D+/u', '', (string)$row['price']) : '',
        'image_url' => (string)$row['image_url'],
        'button_text' => $row['button_text'] !== null ? (string)$row['button_text'] : '',
        'button_url' => $row['button_url'] !== null ? (string)$row['button_url'] : '',
        'is_active' => (int)($row['is_active'] ?? 1),
        'sort_order' => (int)($row['sort_order'] ?? 0),
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

function hs_fetch_items(PDO $pdo, bool $onlyActive = false): array {
    hs_ensure_schema($pdo);
    $sql = 'SELECT id, title, description, price, image_url, button_text, button_url, is_active, sort_order, created_at, updated_at
            FROM home_showcase_items';
    if ($onlyActive) $sql .= ' WHERE is_active = 1';
    $sql .= ' ORDER BY sort_order ASC, id DESC';
    $stmt = $pdo->query($sql);
    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = hs_map_item($row);
    }
    return $items;
}

function hs_next_sort_order(PDO $pdo): int {
    hs_ensure_schema($pdo);
    $stmt = $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM home_showcase_items');
    return (int)$stmt->fetchColumn() + 1;
}

function hs_fetch_item(PDO $pdo, int $id): array {
    hs_ensure_schema($pdo);
    $stmt = $pdo->prepare('SELECT id, title, description, price, image_url, button_text, button_url, is_active, sort_order, created_at, updated_at FROM home_showcase_items WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) hs_fail('Карточка не найдена', 404);
    return hs_map_item($row);
}

function hs_handle_upload(string $field = 'image'): string {
    if (empty($_FILES[$field]['tmp_name'])) {
        hs_fail('Не передан файл');
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        hs_fail('Ошибка загрузки файла');
    }
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        hs_fail('Файл больше 8 МБ');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        hs_fail('Разрешены только JPG, PNG, WEBP');
    }

    $dir = hs_upload_dir();
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        hs_fail('Не удалось создать папку для фото', 500);
    }

    $filename = 'showcase_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        hs_fail('Не удалось сохранить файл', 500);
    }

    return '/home_showcase_cards/' . $filename;
}

function hs_delete_file(?string $url): void {
    $url = trim((string)$url);
    if ($url === '') return;
    $filename = basename(parse_url($url, PHP_URL_PATH) ?: $url);
    if ($filename === '') return;
    $path = hs_upload_dir() . '/' . $filename;
    if (is_file($path)) @unlink($path);
}
