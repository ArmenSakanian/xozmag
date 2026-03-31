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

function tgs_admin_panel_url(?string $supportChatId = null): string
{
    $path = trim((string)(tgs_config()['admin_panel_url'] ?? '/admin/telegram'));
    if ($path === '') {
        $path = '/admin/telegram';
    }

    $base = tgs_detect_base_url();
    $url = preg_match('~^https?://~i', $path) ? $path : $base . '/' . ltrim($path, '/');
    if ($supportChatId !== null && $supportChatId !== '') {
        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator . 'support_chat_id=' . rawurlencode($supportChatId);
    }
    return $url;
}

function tgs_escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tgs_api_request(string $method, array $payload = []): array
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

function tgs_send_message(int|string $chatId, string $text, array $extra = []): array
{
    return tgs_api_request('sendMessage', array_merge([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ], $extra));
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
            PRIMARY KEY (chat_id),
            KEY idx_last_message_at (last_message_at),
            KEY idx_unread_count (unread_count)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS telegram_support_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_id BIGINT NOT NULL,
            user_id BIGINT NULL,
            username VARCHAR(191) NULL,
            first_name VARCHAR(191) NULL,
            last_name VARCHAR(191) NULL,
            direction VARCHAR(16) NOT NULL DEFAULT 'incoming',
            message_text MEDIUMTEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
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

    tgs_add_column_if_missing($pdo, 'telegram_support_threads', 'last_user_message_at', "DATETIME NULL DEFAULT NULL AFTER last_message_at");

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

function tgs_touch_thread(PDO $pdo, int|string $chatId, array $from): void
{
    tgs_ensure_tables($pdo);
    $sql = "
        INSERT INTO telegram_support_threads (chat_id, user_id, username, first_name, last_name)
        VALUES (:chat_id, :user_id, :username, :first_name, :last_name)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            username = VALUES(username),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            updated_at = CURRENT_TIMESTAMP
    ";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':chat_id' => (string)$chatId,
        ':user_id' => isset($from['id']) ? (string)$from['id'] : null,
        ':username' => $from['username'] ?? null,
        ':first_name' => $from['first_name'] ?? null,
        ':last_name' => $from['last_name'] ?? null,
    ]);
}

function tgs_store_incoming_message(PDO $pdo, int|string $chatId, array $from, string $text): void
{
    tgs_touch_thread($pdo, $chatId, $from);

    $insert = $pdo->prepare(
        'INSERT INTO telegram_support_messages (chat_id, user_id, username, first_name, last_name, direction, message_text, is_read)
         VALUES (:chat_id, :user_id, :username, :first_name, :last_name, :direction, :message_text, 0)'
    );
    $insert->execute([
        ':chat_id' => (string)$chatId,
        ':user_id' => isset($from['id']) ? (string)$from['id'] : null,
        ':username' => $from['username'] ?? null,
        ':first_name' => $from['first_name'] ?? null,
        ':last_name' => $from['last_name'] ?? null,
        ':direction' => 'incoming',
        ':message_text' => $text,
    ]);

    $update = $pdo->prepare(
        'UPDATE telegram_support_threads
         SET last_message_text = ?,
             last_message_at = NOW(),
             last_user_message_at = NOW(),
             unread_count = unread_count + 1
         WHERE chat_id = ?'
    );
    $update->execute([$text, (string)$chatId]);
}

function tgs_mark_thread_as_read(PDO $pdo, int|string $chatId): void
{
    tgs_ensure_tables($pdo);
    $st = $pdo->prepare('UPDATE telegram_support_messages SET is_read = 1 WHERE chat_id = ? AND is_read = 0');
    $st->execute([(string)$chatId]);

    $st = $pdo->prepare('UPDATE telegram_support_threads SET unread_count = 0 WHERE chat_id = ?');
    $st->execute([(string)$chatId]);
}

function tgs_send_user_welcome(int|string $chatId): void
{
    $botName = trim((string)(tgs_config()['bot_name'] ?? 'Техподдержка магазина'));
    tgs_send_message($chatId, 'Здравствуйте. Вы открыли бот «' . tgs_escape_html($botName) . '».\n\nНапишите интересующий Вас вопрос одним сообщением. Сотрудник магазина ознакомится с обращением как можно скорее.');
}

function tgs_send_user_ack(int|string $chatId): void
{
    tgs_send_message($chatId, 'Ваше обращение получено и передано сотрудникам магазина. Ожидайте, пожалуйста, обработки сообщения.');
}

function tgs_send_operator_welcome(int|string $chatId): void
{
    tgs_send_message($chatId, 'Уведомления техподдержки подключены.\n\nТеперь Вы будете получать уведомления о новых сообщениях пользователей.');
}

function tgs_notify_operator_new_message(PDO $pdo, int|string $chatId, array $from): void
{
    $operatorChatId = tgs_operator_chat_id($pdo);
    if ($operatorChatId === '' || $operatorChatId === (string)$chatId) {
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

    $countSt = $pdo->prepare('SELECT unread_count FROM telegram_support_threads WHERE chat_id = ? LIMIT 1');
    $countSt->execute([(string)$chatId]);
    $unreadCount = (int)($countSt->fetchColumn() ?: 0);

    $text = "У Вас есть новое сообщение в техподдержке.\n\n"
        . 'Пользователь: <b>' . tgs_escape_html($displayName) . "</b>\n"
        . 'Username: <b>' . tgs_escape_html($userLine) . "</b>\n"
        . 'Chat ID: <code>' . tgs_escape_html((string)$chatId) . "</code>\n"
        . 'Непрочитанных сообщений: <b>' . $unreadCount . '</b>';

    $replyMarkup = [
        'inline_keyboard' => [
            [[
                'text' => 'Открыть раздел техподдержки',
                'url' => tgs_admin_panel_url((string)$chatId),
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
