<?php

declare(strict_types=1);

header_remove('X-Powered-By');

require_once __DIR__ . '/../db.php';

function tgs_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
        if (!is_array($config)) {
            throw new RuntimeException('Некорректный config.php для бота техподдержки');
        }
    }
    return $config;
}

function tgs_json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function tgs_bot_token(): string
{
    $token = trim((string)(tgs_config()['bot_token'] ?? ''));
    if ($token === '') {
        throw new RuntimeException('Не заполнен bot_token в api/telegram_support/config.php');
    }
    return $token;
}

function tgs_webhook_secret(): string
{
    return trim((string)(tgs_config()['webhook_secret'] ?? ''));
}

function tgs_detect_base_url(): string
{
    $cfgUrl = trim((string)(tgs_config()['site_url'] ?? ''));
    if ($cfgUrl !== '') {
        return rtrim($cfgUrl, '/');
    }

    $scheme = 'http';
    $https = $_SERVER['HTTPS'] ?? '';
    $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($https === 'on' || $https === '1' || strtolower((string)$forwarded) === 'https') {
        $scheme = 'https';
    }

    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        throw new RuntimeException('Не удалось определить домен сайта');
    }

    return $scheme . '://' . $host;
}

function tgs_admin_panel_url(?string $supportChatId = null, ?int $conversationId = null): string
{
    $path = trim((string)(tgs_config()['admin_panel_url'] ?? '/admin/telegram'));
    if ($path === '') {
        $path = '/admin/telegram';
    }

    $base = tgs_detect_base_url();
    $url = preg_match('~^https?://~i', $path) ? $path : $base . '/' . ltrim($path, '/');
    $separator = str_contains($url, '?') ? '&' : '?';
    $url .= $separator . 'tab=support';

    if ($conversationId !== null && $conversationId > 0) {
        $url .= '&support_thread_id=' . rawurlencode((string)$conversationId);
    } elseif ($supportChatId !== null && $supportChatId !== '') {
        $url .= '&support_chat_id=' . rawurlencode($supportChatId);
    }

    return $url;
}

function tgs_escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tgs_api_json_request(string $method, array $payload = []): array
{
    $url = 'https://api.telegram.org/bot' . tgs_bot_token() . '/' . $method;
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Не удалось подготовить запрос к Telegram API');
    }

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($json),
    ];

    $body = false;
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Ошибка cURL при обращении к Telegram API: ' . $error);
        }
        $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $json,
                'timeout' => 25,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        if (preg_match('~\s(\d{3})\s~', (string)$statusLine, $m)) {
            $httpCode = (int)$m[1];
        }
        if ($body === false) {
            throw new RuntimeException('Не удалось выполнить HTTP-запрос к Telegram API');
        }
    }

    $data = json_decode((string)$body, true);
    if (!is_array($data)) {
        throw new RuntimeException('Telegram API вернул некорректный JSON. HTTP ' . $httpCode);
    }
    if (empty($data['ok'])) {
        throw new RuntimeException((string)($data['description'] ?? 'Unknown Telegram API error'));
    }

    return $data;
}

function tgs_api_multipart_request(string $method, array $fields): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Для отправки файлов на этом хостинге требуется расширение cURL');
    }

    $url = 'https://api.telegram.org/bot' . tgs_bot_token() . '/' . $method;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ]);

    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Ошибка cURL при отправке файла в Telegram: ' . $error);
    }

    $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $data = json_decode((string)$body, true);
    if (!is_array($data)) {
        throw new RuntimeException('Telegram API вернул некорректный JSON. HTTP ' . $httpCode);
    }
    if (empty($data['ok'])) {
        throw new RuntimeException((string)($data['description'] ?? 'Unknown Telegram API error'));
    }

    return $data;
}

function tgs_api_request(string $method, array $payload = []): array
{
    return tgs_api_json_request($method, $payload);
}

function tgs_send_message(int|string $chatId, string $text, array $extra = []): array
{
    return tgs_api_request('sendMessage', array_merge([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ], $extra));
}

function tgs_send_photo(int|string $chatId, string $photo, string $caption = '', bool $isLocalFile = false, array $extra = []): array
{
    $fields = array_merge([
        'chat_id' => (string)$chatId,
        'caption' => $caption,
        'parse_mode' => 'HTML',
    ], $extra);

    if ($isLocalFile) {
        $fields['photo'] = new CURLFile($photo);
        return tgs_api_multipart_request('sendPhoto', $fields);
    }

    return tgs_api_request('sendPhoto', array_merge($fields, [
        'photo' => $photo,
    ]));
}

function tgs_edit_telegram_message_text(int|string $chatId, int $messageId, string $text): array
{
    return tgs_api_request('editMessageText', [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => tgs_escape_html($text),
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ]);
}

function tgs_edit_telegram_message_caption(int|string $chatId, int $messageId, string $caption): array
{
    return tgs_api_request('editMessageCaption', [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'caption' => tgs_escape_html($caption),
        'parse_mode' => 'HTML',
    ]);
}

function tgs_delete_telegram_message(int|string $chatId, int $messageId): void
{
    tgs_api_request('deleteMessage', [
        'chat_id' => $chatId,
        'message_id' => $messageId,
    ]);
}

function tgs_get_file_path(string $fileId): string
{
    $data = tgs_api_request('getFile', ['file_id' => $fileId]);
    $path = trim((string)($data['result']['file_path'] ?? ''));
    if ($path === '') {
        throw new RuntimeException('Telegram не вернул file_path для файла');
    }
    return $path;
}

function tgs_download_file_bytes(string $filePath): string
{
    $url = 'https://api.telegram.org/file/bot' . tgs_bot_token() . '/' . ltrim($filePath, '/');

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Ошибка cURL при загрузке файла Telegram: ' . $error);
        }
        $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($httpCode >= 400) {
            throw new RuntimeException('Не удалось загрузить файл Telegram. HTTP ' . $httpCode);
        }
        return (string)$body;
    }

    $body = @file_get_contents($url);
    if ($body === false) {
        throw new RuntimeException('Не удалось загрузить файл Telegram');
    }
    return (string)$body;
}

function tgs_table_has_column(PDO $pdo, string $table, string $column): bool
{
    $st = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $st->execute([$table, $column]);
    return (bool)$st->fetchColumn();
}

function tgs_add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (tgs_table_has_column($pdo, $table, $column)) {
        return;
    }
    $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
}

function tgs_upload_dir(): string
{
    $dir = dirname(__DIR__, 2) . '/uploads/support_chat';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function tgs_guess_mime_from_extension(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => 'application/octet-stream',
    };
}

function tgs_ensure_tables(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS telegram_support_threads (
            chat_id BIGINT NOT NULL,
            user_id BIGINT NULL,
            username VARCHAR(191) NULL,
            first_name VARCHAR(191) NULL,
            last_name VARCHAR(191) NULL,
            last_message_text MEDIUMTEXT NULL,
            last_message_at DATETIME NULL DEFAULT NULL,
            unread_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (chat_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS telegram_support_conversations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_id BIGINT NOT NULL,
            user_id BIGINT NULL,
            username VARCHAR(191) NULL,
            first_name VARCHAR(191) NULL,
            last_name VARCHAR(191) NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'active',
            last_message_text MEDIUMTEXT NULL,
            last_message_at DATETIME NULL DEFAULT NULL,
            last_user_message_at DATETIME NULL DEFAULT NULL,
            last_admin_message_at DATETIME NULL DEFAULT NULL,
            unread_count INT NOT NULL DEFAULT 0,
            ack_sent TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            closed_at DATETIME NULL DEFAULT NULL,
            archived_at DATETIME NULL DEFAULT NULL,
            deleted_at DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_chat_status (chat_id, status),
            KEY idx_status_updated (status, updated_at),
            KEY idx_deleted_at (deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS telegram_support_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id BIGINT UNSIGNED NULL,
            chat_id BIGINT NOT NULL,
            user_id BIGINT NULL,
            username VARCHAR(191) NULL,
            first_name VARCHAR(191) NULL,
            last_name VARCHAR(191) NULL,
            direction VARCHAR(16) NOT NULL DEFAULT 'incoming',
            content_type VARCHAR(16) NOT NULL DEFAULT 'text',
            message_text MEDIUMTEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            telegram_message_id BIGINT NULL DEFAULT NULL,
            media_file_id VARCHAR(255) NULL DEFAULT NULL,
            media_path VARCHAR(255) NULL DEFAULT NULL,
            media_mime VARCHAR(100) NULL DEFAULT NULL,
            edited_at DATETIME NULL DEFAULT NULL,
            deleted_at DATETIME NULL DEFAULT NULL,
            deleted_for_all TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_conversation_id (conversation_id),
            KEY idx_chat_id (chat_id),
            KEY idx_is_read (is_read),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS telegram_support_settings (
            setting_key VARCHAR(64) NOT NULL,
            setting_value MEDIUMTEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'conversation_id', "BIGINT UNSIGNED NULL DEFAULT NULL AFTER id");
    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'content_type', "VARCHAR(16) NOT NULL DEFAULT 'text' AFTER direction");
    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'telegram_message_id', "BIGINT NULL DEFAULT NULL AFTER is_read");
    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'media_file_id', "VARCHAR(255) NULL DEFAULT NULL AFTER telegram_message_id");
    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'media_path', "VARCHAR(255) NULL DEFAULT NULL AFTER media_file_id");
    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'media_mime', "VARCHAR(100) NULL DEFAULT NULL AFTER media_path");
    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'edited_at', "DATETIME NULL DEFAULT NULL AFTER media_mime");
    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'deleted_at', "DATETIME NULL DEFAULT NULL AFTER edited_at");
    tgs_add_column_if_missing($pdo, 'telegram_support_messages', 'deleted_for_all', "TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_at");

    tgs_migrate_legacy_support_data($pdo);

    $done = true;
}

function tgs_migrate_legacy_support_data(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $legacyThreads = $pdo->query('SELECT chat_id, user_id, username, first_name, last_name, last_message_text, last_message_at, unread_count, created_at, updated_at FROM telegram_support_threads')->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($legacyThreads as $legacy) {
        $st = $pdo->prepare('SELECT id, deleted_at FROM telegram_support_conversations WHERE chat_id = ? ORDER BY id DESC LIMIT 1');
        $st->execute([(string)$legacy['chat_id']]);
        $latestConversation = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        $conversationId = (int)($latestConversation['id'] ?? 0);

        if ($conversationId <= 0) {
            $insert = $pdo->prepare(
                'INSERT INTO telegram_support_conversations (chat_id, user_id, username, first_name, last_name, status, last_message_text, last_message_at, unread_count, ack_sent, created_at, updated_at)
                 VALUES (:chat_id, :user_id, :username, :first_name, :last_name, :status, :last_message_text, :last_message_at, :unread_count, :ack_sent, :created_at, :updated_at)'
            );
            $insert->execute([
                ':chat_id' => (string)$legacy['chat_id'],
                ':user_id' => $legacy['user_id'] ?: null,
                ':username' => $legacy['username'] ?: null,
                ':first_name' => $legacy['first_name'] ?: null,
                ':last_name' => $legacy['last_name'] ?: null,
                ':status' => 'active',
                ':last_message_text' => $legacy['last_message_text'] ?: null,
                ':last_message_at' => $legacy['last_message_at'] ?: null,
                ':unread_count' => (int)($legacy['unread_count'] ?? 0),
                ':ack_sent' => !empty($legacy['last_message_at']) ? 1 : 0,
                ':created_at' => $legacy['created_at'] ?: date('Y-m-d H:i:s'),
                ':updated_at' => $legacy['updated_at'] ?: date('Y-m-d H:i:s'),
            ]);
            $conversationId = (int)$pdo->lastInsertId();
        }

        if ($conversationId > 0 && !empty($latestConversation['deleted_at'])) {
            continue;
        }

        $upd = $pdo->prepare('UPDATE telegram_support_messages SET conversation_id = ? WHERE chat_id = ? AND conversation_id IS NULL');
        $upd->execute([$conversationId, (string)$legacy['chat_id']]);
        tgs_refresh_conversation_state($pdo, $conversationId);
    }

    $orphans = $pdo->query('SELECT DISTINCT chat_id FROM telegram_support_messages WHERE conversation_id IS NULL ORDER BY chat_id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($orphans as $chatId) {
        $st = $pdo->prepare('SELECT id, deleted_at FROM telegram_support_conversations WHERE chat_id = ? ORDER BY id DESC LIMIT 1');
        $st->execute([(string)$chatId]);
        $latestConversation = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        $conversationId = (int)($latestConversation['id'] ?? 0);
        if ($conversationId <= 0) {
            $meta = $pdo->prepare('SELECT user_id, username, first_name, last_name, MIN(created_at) AS created_at, MAX(created_at) AS updated_at FROM telegram_support_messages WHERE chat_id = ?');
            $meta->execute([(string)$chatId]);
            $row = $meta->fetch(PDO::FETCH_ASSOC) ?: [];
            $insert = $pdo->prepare(
                'INSERT INTO telegram_support_conversations (chat_id, user_id, username, first_name, last_name, status, ack_sent, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)'
            );
            $createdAt = $row['created_at'] ?? date('Y-m-d H:i:s');
            $updatedAt = $row['updated_at'] ?? $createdAt;
            $insert->execute([
                (string)$chatId,
                $row['user_id'] ?: null,
                $row['username'] ?: null,
                $row['first_name'] ?: null,
                $row['last_name'] ?: null,
                'active',
                $createdAt,
                $updatedAt,
            ]);
            $conversationId = (int)$pdo->lastInsertId();
        }
        if ($conversationId > 0 && !empty($latestConversation['deleted_at'])) {
            continue;
        }
        $upd = $pdo->prepare('UPDATE telegram_support_messages SET conversation_id = ? WHERE chat_id = ? AND conversation_id IS NULL');
        $upd->execute([$conversationId, (string)$chatId]);
        tgs_refresh_conversation_state($pdo, $conversationId);
    }

    $done = true;
}

function tgs_get_setting(PDO $pdo, string $key): ?string
{
    tgs_ensure_tables($pdo);
    $st = $pdo->prepare('SELECT setting_value FROM telegram_support_settings WHERE setting_key = ? LIMIT 1');
    $st->execute([$key]);
    $value = $st->fetchColumn();
    return $value === false ? null : (string)$value;
}

function tgs_set_setting(PDO $pdo, string $key, ?string $value): void
{
    tgs_ensure_tables($pdo);
    $st = $pdo->prepare('INSERT INTO telegram_support_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $st->execute([$key, $value]);
}

function tgs_operator_username(): string
{
    return ltrim(trim((string)(tgs_config()['operator_username'] ?? '')), '@');
}

function tgs_operator_chat_id(PDO $pdo): string
{
    $configChatId = trim((string)(tgs_config()['operator_chat_id'] ?? ''));
    if ($configChatId !== '') {
        return $configChatId;
    }
    return trim((string)(tgs_get_setting($pdo, 'operator_chat_id') ?? ''));
}

function tgs_maybe_register_operator(PDO $pdo, int|string $chatId, array $from): bool
{
    $expectedUsername = mb_strtolower(tgs_operator_username(), 'UTF-8');
    $currentUsername = mb_strtolower(trim((string)($from['username'] ?? '')), 'UTF-8');
    if ($expectedUsername === '' || $currentUsername === '' || $expectedUsername !== $currentUsername) {
        return false;
    }

    if (tgs_operator_chat_id($pdo) !== (string)$chatId) {
        tgs_set_setting($pdo, 'operator_chat_id', (string)$chatId);
    }
    return true;
}

function tgs_is_operator(PDO $pdo, int|string $chatId, array $from): bool
{
    if (tgs_maybe_register_operator($pdo, $chatId, $from)) {
        return true;
    }

    $operatorChatId = tgs_operator_chat_id($pdo);
    if ($operatorChatId !== '' && $operatorChatId === (string)$chatId) {
        return true;
    }

    $expectedUsername = mb_strtolower(tgs_operator_username(), 'UTF-8');
    $currentUsername = mb_strtolower(trim((string)($from['username'] ?? '')), 'UTF-8');
    return $expectedUsername !== '' && $currentUsername !== '' && $expectedUsername === $currentUsername;
}

function tgs_get_conversation(PDO $pdo, int $conversationId): ?array
{
    tgs_ensure_tables($pdo);
    $st = $pdo->prepare('SELECT * FROM telegram_support_conversations WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $st->execute([$conversationId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function tgs_get_latest_conversation_by_chat_id(PDO $pdo, int|string $chatId): ?array
{
    tgs_ensure_tables($pdo);
    $st = $pdo->prepare('SELECT * FROM telegram_support_conversations WHERE chat_id = ? AND deleted_at IS NULL ORDER BY CASE status WHEN "active" THEN 0 WHEN "closed" THEN 1 ELSE 2 END, id DESC LIMIT 1');
    $st->execute([(string)$chatId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function tgs_get_active_conversation_by_chat_id(PDO $pdo, int|string $chatId): ?array
{
    tgs_ensure_tables($pdo);
    $st = $pdo->prepare("SELECT * FROM telegram_support_conversations WHERE chat_id = ? AND status = 'active' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
    $st->execute([(string)$chatId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function tgs_create_conversation(PDO $pdo, int|string $chatId, array $from): array
{
    tgs_ensure_tables($pdo);
    $insert = $pdo->prepare(
        "INSERT INTO telegram_support_conversations (chat_id, user_id, username, first_name, last_name, status, ack_sent)
         VALUES (:chat_id, :user_id, :username, :first_name, :last_name, 'active', 0)"
    );
    $insert->execute([
        ':chat_id' => (string)$chatId,
        ':user_id' => isset($from['id']) ? (string)$from['id'] : null,
        ':username' => $from['username'] ?? null,
        ':first_name' => $from['first_name'] ?? null,
        ':last_name' => $from['last_name'] ?? null,
    ]);
    return tgs_get_conversation($pdo, (int)$pdo->lastInsertId()) ?? throw new RuntimeException('Не удалось создать диалог техподдержки');
}

function tgs_touch_conversation(PDO $pdo, int $conversationId, array $from): void
{
    $st = $pdo->prepare(
        'UPDATE telegram_support_conversations
         SET user_id = :user_id, username = :username, first_name = :first_name, last_name = :last_name, updated_at = CURRENT_TIMESTAMP
         WHERE id = :id'
    );
    $st->execute([
        ':id' => $conversationId,
        ':user_id' => isset($from['id']) ? (string)$from['id'] : null,
        ':username' => $from['username'] ?? null,
        ':first_name' => $from['first_name'] ?? null,
        ':last_name' => $from['last_name'] ?? null,
    ]);
}

function tgs_resolve_incoming_conversation(PDO $pdo, int|string $chatId, array $from): array
{
    $conversation = tgs_get_active_conversation_by_chat_id($pdo, $chatId);
    if ($conversation === null) {
        $conversation = tgs_create_conversation($pdo, $chatId, $from);
    } else {
        tgs_touch_conversation($pdo, (int)$conversation['id'], $from);
        $conversation = tgs_get_conversation($pdo, (int)$conversation['id']) ?? $conversation;
    }
    return $conversation;
}

function tgs_mark_conversation_as_read(PDO $pdo, int $conversationId): void
{
    tgs_ensure_tables($pdo);
    $st = $pdo->prepare("UPDATE telegram_support_messages SET is_read = 1 WHERE conversation_id = ? AND direction = 'incoming' AND is_read = 0 AND deleted_at IS NULL");
    $st->execute([$conversationId]);

    $st = $pdo->prepare('UPDATE telegram_support_conversations SET unread_count = 0 WHERE id = ?');
    $st->execute([$conversationId]);
}

function tgs_send_user_welcome(int|string $chatId): void
{
    $botName = trim((string)(tgs_config()['bot_name'] ?? 'Техподдержка магазина'));
    tgs_send_message($chatId, "Здравствуйте. Вы открыли бот «" . tgs_escape_html($botName) . "».\n\nНапишите интересующий Вас вопрос одним сообщением. Сотрудник магазина ознакомится с обращением как можно скорее.");
}

function tgs_send_user_ack(int|string $chatId): void
{
    tgs_send_message($chatId, 'Ваше обращение получено и передано сотрудникам магазина. Ожидайте, пожалуйста, обработки сообщения.');
}

function tgs_send_operator_welcome(int|string $chatId): void
{
    tgs_send_message($chatId, 'Уведомления техподдержки подключены.\n\nТеперь Вы будете получать уведомления о новых сообщениях пользователей.');
}

function tgs_notify_operator_new_message(PDO $pdo, array $conversation, array $from): void
{
    $operatorChatId = tgs_operator_chat_id($pdo);
    $chatId = (string)($conversation['chat_id'] ?? '');
    if ($operatorChatId === '' || $operatorChatId === $chatId) {
        return;
    }

    $displayName = trim(implode(' ', array_filter([
        (string)($from['first_name'] ?? ''),
        (string)($from['last_name'] ?? ''),
    ])));
    if ($displayName === '') {
        $displayName = trim((string)($from['username'] ?? ''));
    }
    if ($displayName === '') {
        $displayName = 'Пользователь без имени';
    }

    $username = trim((string)($from['username'] ?? ''));
    $userLine = $username !== '' ? '@' . $username : 'не указан';
    $unreadCount = (int)($conversation['unread_count'] ?? 0);
    $conversationId = (int)($conversation['id'] ?? 0);

    $text = "У Вас есть новое сообщение в техподдержке.\n\n"
        . 'Пользователь: <b>' . tgs_escape_html($displayName) . "</b>\n"
        . 'Username: <b>' . tgs_escape_html($userLine) . "</b>\n"
        . 'Chat ID: <code>' . tgs_escape_html($chatId) . "</code>\n"
        . 'Непрочитанных сообщений: <b>' . $unreadCount . '</b>';

    $replyMarkup = [
        'inline_keyboard' => [
            [[
                'text' => 'Открыть раздел техподдержки',
                'url' => tgs_admin_panel_url($chatId, $conversationId > 0 ? $conversationId : null),
            ]],
        ],
    ];

    try {
        tgs_send_message($operatorChatId, $text, [
            'reply_markup' => $replyMarkup,
            'disable_web_page_preview' => true,
        ]);
    } catch (Throwable $e) {
        error_log('Support bot operator notify failed: ' . $e->getMessage());
    }
}

function tgs_store_incoming_message(PDO $pdo, int|string $chatId, array $from, array $message): array
{
    $conversation = tgs_resolve_incoming_conversation($pdo, $chatId, $from);
    $conversationId = (int)$conversation['id'];

    $text = trim((string)($message['text'] ?? ''));
    $contentType = 'text';
    $mediaFileId = null;
    $mediaPath = null;
    $mediaMime = null;

    if (!empty($message['photo']) && is_array($message['photo'])) {
        $contentType = 'photo';
        $lastPhoto = end($message['photo']);
        if (is_array($lastPhoto)) {
            $mediaFileId = trim((string)($lastPhoto['file_id'] ?? '')) ?: null;
        }
        $caption = trim((string)($message['caption'] ?? ''));
        $text = $caption !== '' ? $caption : 'Фотография';
    }

    $text = mb_substr($text, 0, 4000, 'UTF-8');

    $insert = $pdo->prepare(
        'INSERT INTO telegram_support_messages (conversation_id, chat_id, user_id, username, first_name, last_name, direction, content_type, message_text, is_read, telegram_message_id, media_file_id, media_path, media_mime)
         VALUES (:conversation_id, :chat_id, :user_id, :username, :first_name, :last_name, :direction, :content_type, :message_text, 0, :telegram_message_id, :media_file_id, :media_path, :media_mime)'
    );
    $insert->execute([
        ':conversation_id' => $conversationId,
        ':chat_id' => (string)$chatId,
        ':user_id' => isset($from['id']) ? (string)$from['id'] : null,
        ':username' => $from['username'] ?? null,
        ':first_name' => $from['first_name'] ?? null,
        ':last_name' => $from['last_name'] ?? null,
        ':direction' => 'incoming',
        ':content_type' => $contentType,
        ':message_text' => $text,
        ':telegram_message_id' => isset($message['message_id']) ? (int)$message['message_id'] : null,
        ':media_file_id' => $mediaFileId,
        ':media_path' => $mediaPath,
        ':media_mime' => $mediaMime,
    ]);

    tgs_refresh_conversation_state($pdo, $conversationId);
    return tgs_get_conversation($pdo, $conversationId) ?? $conversation;
}

function tgs_acknowledge_conversation_if_needed(PDO $pdo, int $conversationId): void
{
    $conversation = tgs_get_conversation($pdo, $conversationId);
    if ($conversation === null) {
        return;
    }
    if ((int)($conversation['ack_sent'] ?? 0) === 1) {
        return;
    }

    tgs_send_user_ack((string)$conversation['chat_id']);
    $st = $pdo->prepare('UPDATE telegram_support_conversations SET ack_sent = 1 WHERE id = ?');
    $st->execute([$conversationId]);
}

function tgs_store_outgoing_text(PDO $pdo, int $conversationId, string $text, ?int $telegramMessageId = null): void
{
    $conversation = tgs_get_conversation($pdo, $conversationId);
    if ($conversation === null) {
        throw new RuntimeException('Диалог не найден');
    }

    $insert = $pdo->prepare(
        'INSERT INTO telegram_support_messages (conversation_id, chat_id, user_id, username, first_name, last_name, direction, content_type, message_text, is_read, telegram_message_id)
         VALUES (:conversation_id, :chat_id, :user_id, :username, :first_name, :last_name, :direction, :content_type, :message_text, 1, :telegram_message_id)'
    );
    $insert->execute([
        ':conversation_id' => $conversationId,
        ':chat_id' => (string)$conversation['chat_id'],
        ':user_id' => $conversation['user_id'] ?? null,
        ':username' => $conversation['username'] ?? null,
        ':first_name' => $conversation['first_name'] ?? null,
        ':last_name' => $conversation['last_name'] ?? null,
        ':direction' => 'outgoing',
        ':content_type' => 'text',
        ':message_text' => $text,
        ':telegram_message_id' => $telegramMessageId,
    ]);

    tgs_refresh_conversation_state($pdo, $conversationId);
}

function tgs_store_outgoing_photo(PDO $pdo, int $conversationId, string $text, string $localPath, string $mime, ?string $telegramFileId = null, ?int $telegramMessageId = null): void
{
    $conversation = tgs_get_conversation($pdo, $conversationId);
    if ($conversation === null) {
        throw new RuntimeException('Диалог не найден');
    }

    $text = trim($text) !== '' ? trim($text) : 'Фотография';

    $insert = $pdo->prepare(
        'INSERT INTO telegram_support_messages (conversation_id, chat_id, user_id, username, first_name, last_name, direction, content_type, message_text, is_read, telegram_message_id, media_file_id, media_path, media_mime)
         VALUES (:conversation_id, :chat_id, :user_id, :username, :first_name, :last_name, :direction, :content_type, :message_text, 1, :telegram_message_id, :media_file_id, :media_path, :media_mime)'
    );
    $insert->execute([
        ':conversation_id' => $conversationId,
        ':chat_id' => (string)$conversation['chat_id'],
        ':user_id' => $conversation['user_id'] ?? null,
        ':username' => $conversation['username'] ?? null,
        ':first_name' => $conversation['first_name'] ?? null,
        ':last_name' => $conversation['last_name'] ?? null,
        ':direction' => 'outgoing',
        ':content_type' => 'photo',
        ':message_text' => mb_substr($text, 0, 4000, 'UTF-8'),
        ':telegram_message_id' => $telegramMessageId,
        ':media_file_id' => $telegramFileId,
        ':media_path' => $localPath,
        ':media_mime' => $mime,
    ]);

    tgs_refresh_conversation_state($pdo, $conversationId);
}

function tgs_refresh_conversation_state(PDO $pdo, int $conversationId): void
{
    $lastSt = $pdo->prepare(
        "SELECT message_text, created_at
         FROM telegram_support_messages
         WHERE conversation_id = ? AND deleted_at IS NULL
         ORDER BY id DESC
         LIMIT 1"
    );
    $lastSt->execute([$conversationId]);
    $last = $lastSt->fetch(PDO::FETCH_ASSOC) ?: null;

    $lastUserSt = $pdo->prepare(
        "SELECT created_at
         FROM telegram_support_messages
         WHERE conversation_id = ? AND direction = 'incoming' AND deleted_at IS NULL
         ORDER BY id DESC
         LIMIT 1"
    );
    $lastUserSt->execute([$conversationId]);
    $lastUserAt = $lastUserSt->fetchColumn() ?: null;

    $lastAdminSt = $pdo->prepare(
        "SELECT created_at
         FROM telegram_support_messages
         WHERE conversation_id = ? AND direction = 'outgoing' AND deleted_at IS NULL
         ORDER BY id DESC
         LIMIT 1"
    );
    $lastAdminSt->execute([$conversationId]);
    $lastAdminAt = $lastAdminSt->fetchColumn() ?: null;

    $unreadSt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM telegram_support_messages
         WHERE conversation_id = ? AND direction = 'incoming' AND is_read = 0 AND deleted_at IS NULL"
    );
    $unreadSt->execute([$conversationId]);
    $unreadCount = (int)($unreadSt->fetchColumn() ?: 0);

    $update = $pdo->prepare(
        'UPDATE telegram_support_conversations
         SET last_message_text = :last_message_text,
             last_message_at = :last_message_at,
             last_user_message_at = :last_user_message_at,
             last_admin_message_at = :last_admin_message_at,
             unread_count = :unread_count,
             updated_at = CURRENT_TIMESTAMP
         WHERE id = :id'
    );
    $update->execute([
        ':last_message_text' => $last['message_text'] ?? null,
        ':last_message_at' => $last['created_at'] ?? null,
        ':last_user_message_at' => $lastUserAt ?: null,
        ':last_admin_message_at' => $lastAdminAt ?: null,
        ':unread_count' => $unreadCount,
        ':id' => $conversationId,
    ]);
}

function tgs_send_admin_reply(PDO $pdo, int $conversationId, string $text): array
{
    $text = trim($text);
    if ($text === '') {
        throw new RuntimeException('Текст ответа пустой');
    }

    $conversation = tgs_get_conversation($pdo, $conversationId);
    if ($conversation === null) {
        throw new RuntimeException('Диалог не найден');
    }
    if (($conversation['status'] ?? 'active') !== 'active') {
        throw new RuntimeException('Можно отвечать только в активный чат');
    }

    $response = tgs_send_message((string)$conversation['chat_id'], tgs_escape_html($text));
    $telegramMessageId = isset($response['result']['message_id']) ? (int)$response['result']['message_id'] : null;
    tgs_store_outgoing_text($pdo, $conversationId, $text, $telegramMessageId);

    return [
        'conversation_id' => $conversationId,
        'chat_id' => (string)$conversation['chat_id'],
        'message_id' => $telegramMessageId,
        'type' => 'text',
    ];
}

function tgs_save_uploaded_support_photo(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Не удалось загрузить фотографию');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Файл фотографии не найден');
    }

    $mime = mime_content_type($tmpName) ?: 'application/octet-stream';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
        throw new RuntimeException('Допустимы только изображения JPG, PNG, WEBP или GIF');
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'bin',
    };

    $dir = tgs_upload_dir();
    $fileName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $dir . '/' . $fileName;
    if (!move_uploaded_file($tmpName, $dest)) {
        throw new RuntimeException('Не удалось сохранить фотографию на сервере');
    }

    return [
        'path' => $dest,
        'relative_path' => 'uploads/support_chat/' . $fileName,
        'mime' => $mime,
    ];
}

function tgs_send_admin_photo_reply(PDO $pdo, int $conversationId, array $file, string $caption = ''): array
{
    $conversation = tgs_get_conversation($pdo, $conversationId);
    if ($conversation === null) {
        throw new RuntimeException('Диалог не найден');
    }
    if (($conversation['status'] ?? 'active') !== 'active') {
        throw new RuntimeException('Можно отвечать только в активный чат');
    }

    $saved = tgs_save_uploaded_support_photo($file);
    $caption = trim($caption);
    $telegramCaption = $caption === '' ? '' : tgs_escape_html(mb_substr($caption, 0, 1024, 'UTF-8'));
    $response = tgs_send_photo((string)$conversation['chat_id'], $saved['path'], $telegramCaption, true);

    $telegramMessageId = isset($response['result']['message_id']) ? (int)$response['result']['message_id'] : null;
    $telegramFileId = null;
    if (!empty($response['result']['photo']) && is_array($response['result']['photo'])) {
        $lastPhoto = end($response['result']['photo']);
        if (is_array($lastPhoto)) {
            $telegramFileId = trim((string)($lastPhoto['file_id'] ?? '')) ?: null;
        }
    }

    tgs_store_outgoing_photo($pdo, $conversationId, $caption, $saved['relative_path'], $saved['mime'], $telegramFileId, $telegramMessageId);

    return [
        'conversation_id' => $conversationId,
        'chat_id' => (string)$conversation['chat_id'],
        'message_id' => $telegramMessageId,
        'type' => 'photo',
    ];
}

function tgs_get_message(PDO $pdo, int $messageId): ?array
{
    tgs_ensure_tables($pdo);
    $st = $pdo->prepare('SELECT * FROM telegram_support_messages WHERE id = ? LIMIT 1');
    $st->execute([$messageId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function tgs_edit_message(PDO $pdo, int $messageId, string $newText): array
{
    $message = tgs_get_message($pdo, $messageId);
    if ($message === null || !empty($message['deleted_at'])) {
        throw new RuntimeException('Сообщение не найдено');
    }
    if (($message['direction'] ?? '') !== 'outgoing') {
        throw new RuntimeException('Редактировать можно только сообщения магазина');
    }

    $conversationId = (int)($message['conversation_id'] ?? 0);
    $conversation = $conversationId > 0 ? tgs_get_conversation($pdo, $conversationId) : null;
    if ($conversation === null) {
        throw new RuntimeException('Диалог не найден');
    }

    $telegramMessageId = (int)($message['telegram_message_id'] ?? 0);
    if ($telegramMessageId <= 0) {
        throw new RuntimeException('У сообщения нет Telegram message_id');
    }

    $newText = trim($newText);
    $contentType = (string)($message['content_type'] ?? 'text');

    if ($contentType === 'photo') {
        tgs_edit_telegram_message_caption((string)$message['chat_id'], $telegramMessageId, $newText);
    } else {
        if ($newText === '') {
            throw new RuntimeException('Текст сообщения не может быть пустым');
        }
        tgs_edit_telegram_message_text((string)$message['chat_id'], $telegramMessageId, $newText);
    }

    $st = $pdo->prepare('UPDATE telegram_support_messages SET message_text = ?, edited_at = NOW() WHERE id = ?');
    $st->execute([$contentType === 'photo' && $newText === '' ? 'Фотография' : $newText, $messageId]);
    tgs_refresh_conversation_state($pdo, $conversationId);

    return [
        'message_id' => $messageId,
        'conversation_id' => $conversationId,
        'content_type' => $contentType,
    ];
}

function tgs_delete_message_everywhere(PDO $pdo, int $messageId): array
{
    $message = tgs_get_message($pdo, $messageId);
    if ($message === null) {
        throw new RuntimeException('Сообщение не найдено');
    }
    if (!empty($message['deleted_at'])) {
        return ['already_deleted' => true];
    }

    $telegramMessageId = (int)($message['telegram_message_id'] ?? 0);
    if ($telegramMessageId <= 0) {
        throw new RuntimeException('У сообщения нет Telegram message_id для удаления');
    }

    tgs_delete_telegram_message((string)$message['chat_id'], $telegramMessageId);

    $st = $pdo->prepare('UPDATE telegram_support_messages SET deleted_at = NOW(), deleted_for_all = 1, is_read = 1 WHERE id = ?');
    $st->execute([$messageId]);
    if ((int)($message['conversation_id'] ?? 0) > 0) {
        tgs_refresh_conversation_state($pdo, (int)$message['conversation_id']);
    }

    return [
        'conversation_id' => (int)($message['conversation_id'] ?? 0),
        'chat_id' => (string)$message['chat_id'],
        'message_id' => $messageId,
        'telegram_message_id' => $telegramMessageId,
    ];
}

function tgs_update_conversation_status(PDO $pdo, int $conversationId, string $status): array
{
    $conversation = tgs_get_conversation($pdo, $conversationId);
    if ($conversation === null) {
        throw new RuntimeException('Чат не найден');
    }

    $status = trim($status);
    if (!in_array($status, ['active', 'closed', 'archived'], true)) {
        throw new RuntimeException('Некорректный статус чата');
    }

    $closedAt = $conversation['closed_at'] ?? null;
    $archivedAt = $conversation['archived_at'] ?? null;
    if ($status === 'closed' && $closedAt === null) {
        $closedAt = date('Y-m-d H:i:s');
    }
    if ($status === 'archived' && $archivedAt === null) {
        $archivedAt = date('Y-m-d H:i:s');
    }
    if ($status === 'archived' && $closedAt === null) {
        $closedAt = date('Y-m-d H:i:s');
    }
    if ($status === 'active') {
        $closedAt = null;
        $archivedAt = null;
    }

    $st = $pdo->prepare('UPDATE telegram_support_conversations SET status = ?, closed_at = ?, archived_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $st->execute([$status, $closedAt, $archivedAt, $conversationId]);

    return tgs_get_conversation($pdo, $conversationId) ?? $conversation;
}

function tgs_delete_conversation(PDO $pdo, int $conversationId): array
{
    $conversation = tgs_get_conversation($pdo, $conversationId);
    if ($conversation === null) {
        throw new RuntimeException('Чат не найден');
    }

    $conversationIds = [$conversationId];
    if (($conversation['status'] ?? 'active') === 'active') {
        $dupSt = $pdo->prepare(
            "SELECT id
             FROM telegram_support_conversations
             WHERE chat_id = ? AND status = 'active' AND deleted_at IS NULL"
        );
        $dupSt->execute([(string)$conversation['chat_id']]);
        $conversationIds = array_values(array_unique(array_map('intval', $dupSt->fetchAll(PDO::FETCH_COLUMN) ?: [$conversationId])));
        if (empty($conversationIds)) {
            $conversationIds = [$conversationId];
        }
    }

    $placeholders = implode(',', array_fill(0, count($conversationIds), '?'));
    $messagesSt = $pdo->prepare(
        "SELECT id, telegram_message_id, chat_id
         FROM telegram_support_messages
         WHERE conversation_id IN ($placeholders) AND deleted_at IS NULL
         ORDER BY id ASC"
    );
    $messagesSt->execute($conversationIds);
    $messages = $messagesSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($messages as $row) {
        $tgMessageId = (int)($row['telegram_message_id'] ?? 0);
        if ($tgMessageId > 0) {
            try {
                tgs_delete_telegram_message((string)$row['chat_id'], $tgMessageId);
            } catch (Throwable $e) {
                error_log('Support delete conversation telegram delete failed: ' . $e->getMessage());
            }
        }
    }

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            "UPDATE telegram_support_messages
             SET deleted_at = COALESCE(deleted_at, NOW()), deleted_for_all = 1, is_read = 1
             WHERE conversation_id IN ($placeholders)"
        );
        $st->execute($conversationIds);

        $st = $pdo->prepare(
            "UPDATE telegram_support_conversations
             SET deleted_at = NOW(), updated_at = CURRENT_TIMESTAMP
             WHERE id IN ($placeholders)"
        );
        $st->execute($conversationIds);

        $legacyDeleteSt = $pdo->prepare('DELETE FROM telegram_support_threads WHERE chat_id = ?');
        $legacyDeleteSt->execute([(string)$conversation['chat_id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'conversation_id' => $conversationId,
        'chat_id' => (string)$conversation['chat_id'],
        'deleted_conversation_ids' => $conversationIds,
    ];
}

function tgs_conversation_needs_reply(array $conversation): bool
{
    $lastUser = trim((string)($conversation['last_user_message_at'] ?? ''));
    $lastAdmin = trim((string)($conversation['last_admin_message_at'] ?? ''));
    if (($conversation['status'] ?? 'active') !== 'active') {
        return false;
    }
    if ($lastUser === '') {
        return false;
    }
    if ($lastAdmin === '') {
        return true;
    }
    return strtotime($lastUser) > strtotime($lastAdmin);
}
