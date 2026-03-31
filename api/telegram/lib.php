<?php

declare(strict_types=1);

header_remove('X-Powered-By');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../search/product_finder.php';

function tg_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
        if (!is_array($config)) {
            throw new RuntimeException('Некорректный config.php');
        }
    }
    return $config;
}

function tg_json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function tg_bot_token(): string
{
    $token = trim((string)(tg_config()['bot_token'] ?? ''));
    if ($token === '') {
        throw new RuntimeException('Не заполнен bot_token в api/telegram/config.php');
    }
    return $token;
}

function tg_webhook_secret(): string
{
    return trim((string)(tg_config()['webhook_secret'] ?? ''));
}

function tg_detect_base_url(): string
{
    $cfgUrl = trim((string)(tg_config()['site_url'] ?? ''));
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

function tg_contact_url(): string
{
    $url = trim((string)(tg_config()['contact_url'] ?? '/contact'));
    if ($url === '') {
        $url = '/contact';
    }

    if (preg_match('~^https?://~i', $url)) {
        return $url;
    }

    return tg_detect_base_url() . '/' . ltrim($url, '/');
}

function tg_catalog_search_url(string $query): string
{
    return tg_detect_base_url() . '/catalog?q=' . rawurlencode(trim($query));
}

function tg_api_request(string $method, array $payload = []): array
{
    $url = 'https://api.telegram.org/bot' . tg_bot_token() . '/' . $method;
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

function tg_answer_callback(string $callbackId, string $text = '', bool $alert = false): void
{
    $payload = ['callback_query_id' => $callbackId];
    if ($text !== '') {
        $payload['text'] = $text;
    }
    if ($alert) {
        $payload['show_alert'] = true;
    }

    try {
        tg_api_request('answerCallbackQuery', $payload);
    } catch (Throwable $e) {
        error_log('Telegram answerCallbackQuery failed: ' . $e->getMessage());
    }
}

function tg_send_message(int|string $chatId, string $text, array $extra = []): array
{
    return tg_api_request('sendMessage', array_merge([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ], $extra));
}

function tg_send_photo(int|string $chatId, string $photoUrl, string $caption, array $extra = []): array
{
    return tg_api_request('sendPhoto', array_merge([
        'chat_id' => $chatId,
        'photo' => $photoUrl,
        'caption' => $caption,
        'parse_mode' => 'HTML',
    ], $extra));
}

function tg_reply_main_keyboard(): array
{
    return [
        'keyboard' => [
            [['text' => '🔎 Найти товар']],
            [['text' => '📞 Связаться с магазином']],
        ],
        'resize_keyboard' => true,
        'is_persistent' => true,
        'input_field_placeholder' => 'Выберите действие',
    ];
}

function tg_reply_search_keyboard(): array
{
    return [
        'keyboard' => [
            [['text' => '📝 По названию'], ['text' => '🔢 По штрих-коду']],
            [['text' => '🏷️ По артикулу']],
            [['text' => '↩️ Назад в меню']],
        ],
        'resize_keyboard' => true,
        'is_persistent' => true,
        'input_field_placeholder' => 'Выберите способ поиска',
    ];
}

function tg_reply_back_only_keyboard(): array
{
    return [
        'keyboard' => [
            [['text' => '↩️ Назад в меню']],
        ],
        'resize_keyboard' => true,
        'is_persistent' => true,
    ];
}

function tg_send_main_menu(int|string $chatId, string $text = ''): void
{
    if ($text === '') {
        $botName = trim((string)(tg_config()['bot_name'] ?? 'Всё для дома'));
        $text = 'Здравствуйте. Я - бот магазина «' . tg_escape_html($botName) . "».\n\nВыберите действие.";
    }

    tg_send_message($chatId, $text, [
        'reply_markup' => tg_reply_main_keyboard(),
    ]);
}

function tg_send_search_menu(int|string $chatId, string $text = ''): void
{
    if ($text === '') {
        $text = 'Выберите способ поиска товара.';
    }

    tg_send_message($chatId, $text, [
        'reply_markup' => tg_reply_search_keyboard(),
    ]);
}


function tg_reply_remove_keyboard(): array
{
    return [
        'remove_keyboard' => true,
    ];
}

function tg_policy_version(): string
{
    return 'telegram-bot-policy-v1';
}

function tg_policy_url(): string
{
    return tg_detect_base_url() . '/telegram-bot-privacy';
}

function tg_consent_keyboard(): array
{
    return [
        'inline_keyboard' => [
            [[
                'text' => 'Открыть политику конфиденциальности',
                'url' => tg_policy_url(),
            ]],
            [[
                'text' => 'Принимаю',
                'callback_data' => 'consent:accept',
            ]],
            [[
                'text' => 'Не принимаю',
                'callback_data' => 'consent:decline',
            ]],
        ],
    ];
}

function tg_send_consent_prompt(int|string $chatId, string $text = ''): void
{
    if ($text === '') {
        $text = 'Для дальнейшего использования Telegram-бота необходимо ознакомиться и согласиться с <a href="' . tg_escape_html(tg_policy_url()) . '">Политикой конфиденциальности</a>.

После ознакомления нажмите «Принимаю». До принятия политики использование бота недоступно.';
    }

    tg_send_message($chatId, $text, [
        'reply_markup' => tg_consent_keyboard(),
        'disable_web_page_preview' => false,
    ]);
}

function tg_send_consent_declined_message(int|string $chatId): void
{
    tg_send_message($chatId, 'К сожалению, использование бота недоступно, пока не принята политика конфиденциальности.

Чтобы вернуться к запросу согласия, нажмите /start.', [
        'reply_markup' => tg_reply_remove_keyboard(),
    ]);
}

function tg_table_has_column(PDO $pdo, string $table, string $column): bool
{
    $st = $pdo->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    $st->execute([$table, $column]);
    return (bool)$st->fetchColumn();
}

function tg_add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (tg_table_has_column($pdo, $table, $column)) {
        return;
    }
    $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
}

function tg_ensure_tables(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS telegram_user_states (
            chat_id BIGINT NOT NULL,
            user_id BIGINT NULL,
            username VARCHAR(191) NULL,
            first_name VARCHAR(191) NULL,
            last_name VARCHAR(191) NULL,
            state VARCHAR(64) NOT NULL DEFAULT '',
            state_payload MEDIUMTEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (chat_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    tg_add_column_if_missing($pdo, 'telegram_user_states', 'consent_accepted', "TINYINT(1) NOT NULL DEFAULT 0 AFTER last_name");
    tg_add_column_if_missing($pdo, 'telegram_user_states', 'consent_at', "DATETIME NULL DEFAULT NULL AFTER consent_accepted");
    tg_add_column_if_missing($pdo, 'telegram_user_states', 'consent_version', "VARCHAR(64) NOT NULL DEFAULT '' AFTER consent_at");
    tg_add_column_if_missing($pdo, 'telegram_user_states', 'last_action', "VARCHAR(64) NOT NULL DEFAULT '' AFTER state_payload");
    tg_add_column_if_missing($pdo, 'telegram_user_states', 'last_action_label', "VARCHAR(255) NOT NULL DEFAULT '' AFTER last_action");
    tg_add_column_if_missing($pdo, 'telegram_user_states', 'last_payload', "MEDIUMTEXT NULL DEFAULT NULL AFTER last_action_label");

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS telegram_activity_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_id BIGINT NOT NULL,
            user_id BIGINT NULL,
            username VARCHAR(191) NULL,
            first_name VARCHAR(191) NULL,
            last_name VARCHAR(191) NULL,
            action_code VARCHAR(64) NOT NULL DEFAULT '',
            action_label VARCHAR(255) NOT NULL DEFAULT '',
            payload_json MEDIUMTEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_chat_id (chat_id),
            KEY idx_user_id (user_id),
            KEY idx_action_code (action_code),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $done = true;
}

function tg_touch_user(PDO $pdo, int|string $chatId, array $from = [], string $state = '', ?array $payload = null): void
{
    tg_ensure_tables($pdo);
    $payloadJson = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $sql = "
        INSERT INTO telegram_user_states (chat_id, user_id, username, first_name, last_name, state, state_payload)
        VALUES (:chat_id, :user_id, :username, :first_name, :last_name, :state, :state_payload)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            username = VALUES(username),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            state = VALUES(state),
            state_payload = VALUES(state_payload),
            updated_at = CURRENT_TIMESTAMP
    ";

    $st = $pdo->prepare($sql);
    $st->execute([
        ':chat_id' => (string)$chatId,
        ':user_id' => isset($from['id']) ? (string)$from['id'] : null,
        ':username' => $from['username'] ?? null,
        ':first_name' => $from['first_name'] ?? null,
        ':last_name' => $from['last_name'] ?? null,
        ':state' => $state,
        ':state_payload' => $payloadJson,
    ]);
}

function tg_get_user_state(PDO $pdo, int|string $chatId): array
{
    tg_ensure_tables($pdo);

    $st = $pdo->prepare('SELECT state, state_payload FROM telegram_user_states WHERE chat_id = ? LIMIT 1');
    $st->execute([(string)$chatId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['state' => '', 'payload' => null];
    }

    $payload = null;
    if (!empty($row['state_payload'])) {
        $decoded = json_decode((string)$row['state_payload'], true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    return [
        'state' => (string)($row['state'] ?? ''),
        'payload' => $payload,
    ];
}

function tg_set_user_state(PDO $pdo, int|string $chatId, array $from, string $state, ?array $payload = null): void
{
    tg_touch_user($pdo, $chatId, $from, $state, $payload);
}

function tg_clear_user_state(PDO $pdo, int|string $chatId, array $from = []): void
{
    tg_touch_user($pdo, $chatId, $from, '', null);
}


function tg_get_user_profile(PDO $pdo, int|string $chatId): array
{
    tg_ensure_tables($pdo);

    $st = $pdo->prepare('SELECT * FROM telegram_user_states WHERE chat_id = ? LIMIT 1');
    $st->execute([(string)$chatId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : [];
}

function tg_user_has_consent(PDO $pdo, int|string $chatId): bool
{
    $row = tg_get_user_profile($pdo, $chatId);
    if (empty($row)) {
        return false;
    }

    return (int)($row['consent_accepted'] ?? 0) === 1
        && (string)($row['consent_version'] ?? '') === tg_policy_version();
}

function tg_accept_user_consent(PDO $pdo, int|string $chatId, array $from = []): void
{
    tg_touch_user($pdo, $chatId, $from, '', null);
    $st = $pdo->prepare(
        'UPDATE telegram_user_states
         SET consent_accepted = 1,
             consent_at = COALESCE(consent_at, NOW()),
             consent_version = ?
         WHERE chat_id = ?'
    );
    $st->execute([tg_policy_version(), (string)$chatId]);
}

function tg_decline_user_consent(PDO $pdo, int|string $chatId, array $from = []): void
{
    tg_touch_user($pdo, $chatId, $from, '', null);
    $st = $pdo->prepare(
        'UPDATE telegram_user_states
         SET consent_accepted = 0,
             consent_version = ?
         WHERE chat_id = ?'
    );
    $st->execute([tg_policy_version(), (string)$chatId]);
}

function tg_log_action(PDO $pdo, int|string $chatId, array $from, string $actionCode, string $actionLabel, ?array $payload = null): void
{
    tg_ensure_tables($pdo);

    $stateRow = tg_get_user_state($pdo, $chatId);
    tg_touch_user($pdo, $chatId, $from, (string)($stateRow['state'] ?? ''), is_array($stateRow['payload'] ?? null) ? $stateRow['payload'] : null);

    $payloadJson = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $insert = $pdo->prepare(
        'INSERT INTO telegram_activity_log (chat_id, user_id, username, first_name, last_name, action_code, action_label, payload_json)
         VALUES (:chat_id, :user_id, :username, :first_name, :last_name, :action_code, :action_label, :payload_json)'
    );
    $insert->execute([
        ':chat_id' => (string)$chatId,
        ':user_id' => isset($from['id']) ? (string)$from['id'] : null,
        ':username' => $from['username'] ?? null,
        ':first_name' => $from['first_name'] ?? null,
        ':last_name' => $from['last_name'] ?? null,
        ':action_code' => $actionCode,
        ':action_label' => $actionLabel,
        ':payload_json' => $payloadJson,
    ]);

    $update = $pdo->prepare(
        'UPDATE telegram_user_states
         SET last_action = ?,
             last_action_label = ?,
             last_payload = ?
         WHERE chat_id = ?'
    );
    $update->execute([$actionCode, $actionLabel, $payloadJson, (string)$chatId]);
}

function tg_escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tg_homo_en_to_ru(): array
{
    return [
        'A' => 'А', 'a' => 'а', 'B' => 'В', 'b' => 'в', 'C' => 'С', 'c' => 'с', 'E' => 'Е', 'e' => 'е', 'H' => 'Н', 'h' => 'н',
        'K' => 'К', 'k' => 'к', 'M' => 'М', 'm' => 'м', 'O' => 'О', 'o' => 'о', 'P' => 'Р', 'p' => 'р', 'T' => 'Т', 't' => 'т',
        'X' => 'Х', 'x' => 'х', 'Y' => 'У', 'y' => 'у',
    ];
}

function tg_homo_ru_to_en(): array
{
    return [
        'А' => 'A', 'а' => 'a', 'В' => 'B', 'в' => 'b', 'С' => 'C', 'с' => 'c', 'Е' => 'E', 'е' => 'e', 'Н' => 'H', 'н' => 'h',
        'К' => 'K', 'к' => 'k', 'М' => 'M', 'м' => 'm', 'О' => 'O', 'о' => 'o', 'Р' => 'P', 'р' => 'p', 'Т' => 'T', 'т' => 't',
        'Х' => 'X', 'х' => 'x', 'У' => 'Y', 'у' => 'y',
    ];
}

function tg_kb_en_to_ru(): array
{
    return [
        '`' => 'ё', '~' => 'Ё', 'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з', '[' => 'х', ']' => 'ъ',
        'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л', 'l' => 'д', ';' => 'ж', "'" => 'э',
        'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь', ',' => 'б', '.' => 'ю', '/' => '.',
        '@' => '"', '#' => '№', '$' => ';', '^' => ':', '&' => '?',
        'Q' => 'Й', 'W' => 'Ц', 'E' => 'У', 'R' => 'К', 'T' => 'Е', 'Y' => 'Н', 'U' => 'Г', 'I' => 'Ш', 'O' => 'Щ', 'P' => 'З', '{' => 'Х', '}' => 'Ъ',
        'A' => 'Ф', 'S' => 'Ы', 'D' => 'В', 'F' => 'А', 'G' => 'П', 'H' => 'Р', 'J' => 'О', 'K' => 'Л', 'L' => 'Д', ':' => 'Ж', '"' => 'Э',
        'Z' => 'Я', 'X' => 'Ч', 'C' => 'С', 'V' => 'М', 'B' => 'И', 'N' => 'Т', 'M' => 'Ь', '<' => 'Б', '>' => 'Ю', '?' => ',',
    ];
}

function tg_kb_ru_to_en(): array
{
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (tg_kb_en_to_ru() as $en => $ru) {
            $map[$ru] = $en;
        }
    }
    return $map;
}

function tg_map_chars(string $str, array $map): string
{
    $chars = preg_split('//u', (string)$str, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $out = '';
    foreach ($chars as $ch) {
        $out .= $map[$ch] ?? $ch;
    }
    return $out;
}

function tg_swap_layout(string $str, string $dir = 'en2ru'): string
{
    return tg_map_chars($str, $dir === 'en2ru' ? tg_kb_en_to_ru() : tg_kb_ru_to_en());
}

function tg_make_name_variants(string $input): array
{
    $input = trim($input);
    if ($input === '') {
        return [];
    }

    $variants = [$input];
    $norm = pf_normalize_q($input);
    if ($norm !== '' && $norm !== $input) {
        $variants[] = $norm;
    }

    $swapRu = tg_swap_layout($input, 'en2ru');
    $swapEn = tg_swap_layout($input, 'ru2en');
    $hRu = tg_map_chars($input, tg_homo_en_to_ru());
    $hEn = tg_map_chars($input, tg_homo_ru_to_en());

    foreach ([$swapRu, $swapEn, $hRu, $hEn] as $variant) {
        $variant = trim($variant);
        if ($variant === '' || in_array($variant, $variants, true)) {
            continue;
        }
        $variants[] = $variant;
    }

    return $variants;
}

function tg_make_article_variants(string $input): array
{
    $input = trim($input);
    if ($input === '') {
        return [];
    }

    $variants = [$input];
    $compact = preg_replace('~\s+~u', '', $input);
    if ($compact !== '' && $compact !== $input) {
        $variants[] = $compact;
    }

    foreach ([
        tg_map_chars($input, tg_homo_en_to_ru()),
        tg_map_chars($input, tg_homo_ru_to_en()),
        tg_swap_layout($input, 'en2ru'),
        tg_swap_layout($input, 'ru2en'),
    ] as $variant) {
        $variant = trim($variant);
        if ($variant === '') {
            continue;
        }
        if (!in_array($variant, $variants, true)) {
            $variants[] = $variant;
        }
        $compacted = preg_replace('~\s+~u', '', $variant);
        if ($compacted !== '' && !in_array($compacted, $variants, true)) {
            $variants[] = $compacted;
        }
    }

    return $variants;
}

function tg_find_product_images_by_barcode(string $barcode): array
{
    $barcode = trim($barcode);
    if ($barcode === '') {
        return [];
    }

    $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($docRoot === '') {
        return [];
    }

    $folder = $docRoot . '/photo_product_vitrina/';
    if (!is_dir($folder)) {
        return [];
    }

    $files = glob($folder . $barcode . '*.webp') ?: [];
    if (empty($files)) {
        return [];
    }

    sort($files);
    $out = [];
    foreach ($files as $file) {
        $out[] = '/photo_product_vitrina/' . basename($file);
    }
    return array_values(array_unique($out));
}

function tg_product_images(array $product): array
{
    $images = [];
    if (!empty($product['photo'])) {
        $images = pf_decode_photo_to_images((string)$product['photo']);
    }
    if (empty($images) && !empty($product['barcode'])) {
        $images = tg_find_product_images_by_barcode((string)$product['barcode']);
    }
    return $images;
}

function tg_first_photo_url(array $product): string
{
    $images = tg_product_images($product);
    if (empty($images)) {
        return '';
    }

    $first = trim((string)$images[0]);
    if ($first === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $first)) {
        return $first;
    }
    return tg_detect_base_url() . '/' . ltrim($first, '/');
}

function tg_format_price(mixed $price): string
{
    $num = (float)str_replace(',', '.', (string)$price);
    $hasFraction = abs($num - round($num)) > 0.00001;
    return number_format($num, $hasFraction ? 2 : 0, '.', ' ');
}

function tg_product_url(array $product): string
{
    $slug = trim((string)($product['slug'] ?? ''));
    $key = $slug !== '' ? $slug : (string)($product['id'] ?? '');
    return tg_detect_base_url() . '/product/' . rawurlencode($key);
}

function tg_build_product_text(array $product): string
{
    $lines = [];
    $lines[] = '<b>' . tg_escape_html((string)($product['name'] ?? 'Товар')) . '</b>';

    if (isset($product['price']) && $product['price'] !== '' && $product['price'] !== null) {
        $lines[] = 'Цена: <b>' . tg_escape_html(tg_format_price($product['price'])) . ' ₽</b>';
    }
    if (!empty($product['barcode'])) {
        $lines[] = 'Штрих-код: <code>' . tg_escape_html((string)$product['barcode']) . '</code>';
    }
    if (!empty($product['article'])) {
        $lines[] = 'Артикул: <code>' . tg_escape_html((string)$product['article']) . '</code>';
    }

    $qtyText = pf_format_qty_for_ui($product['quantity'] ?? 0, (string)($product['measure_name'] ?? $product['measureName'] ?? ''));
    $measure = trim((string)($product['measure_name'] ?? $product['measureName'] ?? ''));
    if ($measure !== '') {
        $lines[] = 'Остаток: <b>' . tg_escape_html(trim($qtyText . ' ' . $measure)) . '</b>';
    } else {
        $lines[] = 'Остаток: <b>' . tg_escape_html($qtyText) . '</b>';
    }

    return implode("\n", $lines);
}

function tg_product_open_button(array $product): array
{
    return [
        'inline_keyboard' => [
            [[
                'text' => '🔗 Открыть товар на сайте',
                'url' => tg_product_url($product),
            ]],
            [[
                'text' => '🔎 Найти ещё',
                'callback_data' => 'search_menu',
            ]],
            [[
                'text' => '🏠 Главное меню',
                'callback_data' => 'menu',
            ]],
        ],
    ];
}

function tg_product_choices_keyboard(array $products, string $query): array
{
    $rows = [];
    $n = 1;
    foreach ($products as $product) {
        $label = trim((string)($product['name'] ?? 'Товар'));
        if (mb_strlen($label, 'UTF-8') > 48) {
            $label = mb_substr($label, 0, 45, 'UTF-8') . '...';
        }
        $rows[] = [[
            'text' => $n . '. ' . $label,
            'callback_data' => 'product:' . (int)$product['id'],
        ]];
        $n++;
    }

    $rows[] = [[
        'text' => '🌐 Смотреть все результаты в каталоге',
        'url' => tg_catalog_search_url($query),
    ]];

    $rows[] = [[
        'text' => '🔎 Найти ещё',
        'callback_data' => 'search_menu',
    ]];

    $rows[] = [[
        'text' => '🏠 Главное меню',
        'callback_data' => 'menu',
    ]];

    return ['inline_keyboard' => $rows];
}

function tg_fetch_product_by_id(PDO $pdo, int $id): ?array
{
    return pf_fetch_product_by_id($pdo, $id);
}

function tg_find_product_by_barcode(PDO $pdo, string $barcode): ?array
{
    return pf_find_product_by_barcode($pdo, $barcode);
}

function tg_search_products_by_name(PDO $pdo, string $query, int $limit = 10): array
{
    $limit = max(1, min(10, $limit));
    $variants = tg_make_name_variants($query);
    if (empty($variants)) {
        return [];
    }

    $found = [];
    $seen = [];
    foreach ($variants as $index => $variant) {
        $rows = pf_search_products_by_name($pdo, $variant, $limit);
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $found[] = $row;
            if (count($found) >= $limit) {
                break 2;
            }
        }
        if ($index === 0 && !empty($found)) {
            break;
        }
    }

    return $found;
}

function tg_search_products_by_article(PDO $pdo, string $article, int $limit = 10): array
{
    $limit = max(1, min(10, $limit));
    $variants = tg_make_article_variants($article);
    if (empty($variants)) {
        return [];
    }

    $seen = [];
    $out = [];
    foreach ($variants as $variant) {
        $rows = pf_find_products_by_article($pdo, $variant, $limit);
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $out[] = $row;
            if (count($out) >= $limit) {
                break 2;
            }
        }
        if (!empty($out)) {
            break;
        }
    }

    return $out;
}

function tg_send_contacts(int|string $chatId): void
{
    $phone = trim((string)(tg_config()['store_phone'] ?? ''));
    $text = '📞 <b>Связь с магазином</b>';
    if ($phone !== '') {
        $text .= "
Телефон: <b>" . tg_escape_html($phone) . '</b>';
    }
    $text .= "

Откройте страницу контактов, чтобы посмотреть адрес, телефон и схему проезда.";

    tg_send_message($chatId, $text, [
        'reply_markup' => [
            'inline_keyboard' => [
                [[
                    'text' => '📍 Открыть страницу контактов',
                    'url' => tg_contact_url(),
                ]],
                [[
                    'text' => '🔎 Найти товар',
                    'callback_data' => 'search_menu',
                ]],
            ],
        ],
    ]);
}

function tg_send_product_card(int|string $chatId, array $product): void
{
    $text = tg_build_product_text($product);
    $replyMarkup = tg_product_open_button($product);
    $photoUrl = tg_first_photo_url($product);

    if ($photoUrl !== '') {
        try {
            tg_send_photo($chatId, $photoUrl, $text, [
                'reply_markup' => $replyMarkup,
            ]);
            return;
        } catch (Throwable $e) {
            error_log('Telegram sendPhoto failed: ' . $e->getMessage());
        }
    }

    tg_send_message($chatId, $text, [
        'reply_markup' => $replyMarkup,
    ]);
}

function tg_send_product_choices(int|string $chatId, array $products, string $query): void
{
    $text = 'По запросу <b>' . tg_escape_html($query) . '</b> найдено несколько товаров.'
        . "

Выберите нужный вариант из списка ниже или откройте все результаты в каталоге.";

    tg_send_message($chatId, $text, [
        'reply_markup' => tg_product_choices_keyboard($products, $query),
    ]);
}
