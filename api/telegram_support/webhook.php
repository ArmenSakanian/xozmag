<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$secret = tgs_webhook_secret();
if ($secret !== '') {
    $headerSecret = trim((string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''));
    if (!hash_equals($secret, $headerSecret)) {
        tgs_json_response(['ok' => false, 'error' => 'forbidden'], 403);
    }
}

$raw = file_get_contents('php://input');
$update = json_decode((string)$raw, true);
if (!is_array($update)) {
    tgs_json_response(['ok' => true]);
}

try {
    global $pdo;
    tgs_ensure_tables($pdo);

    $message = $update['message'] ?? null;
    if (!is_array($message)) {
        tgs_json_response(['ok' => true]);
    }

    $chat = $message['chat'] ?? [];
    $chatId = $chat['id'] ?? null;
    $chatType = (string)($chat['type'] ?? '');
    $from = is_array($message['from'] ?? null) ? $message['from'] : [];
    $text = trim((string)($message['text'] ?? ''));

    if ($chatId === null) {
        tgs_json_response(['ok' => true]);
    }

    if ($chatType !== 'private') {
        tgs_send_message($chatId, 'Этот бот работает только в личном диалоге. Откройте чат с ботом и нажмите /start.');
        tgs_json_response(['ok' => true]);
    }

    $isOperator = tgs_is_operator($pdo, $chatId, $from);

    if ($text === '/start' || str_starts_with($text, '/start ')) {
        if ($isOperator) {
            tgs_send_operator_welcome($chatId);
        } else {
            tgs_send_user_welcome($chatId);
        }
        tgs_json_response(['ok' => true]);
    }

    if ($text === '' || str_starts_with($text, '/')) {
        if ($isOperator) {
            tgs_send_message($chatId, 'Уведомления подключены. Новые обращения пользователей будут приходить в этот чат.');
        } else {
            tgs_send_message($chatId, 'Напишите Ваш вопрос одним сообщением. Сотрудник магазина ознакомится с обращением как можно скорее.');
        }
        tgs_json_response(['ok' => true]);
    }

    if ($isOperator) {
        tgs_send_message($chatId, 'Этот бот используется для уведомлений о новых обращениях и приёма сообщений от клиентов. Историю обращений просматривайте в админ-панели сайта.');
        tgs_json_response(['ok' => true]);
    }

    tgs_store_incoming_message($pdo, $chatId, $from, mb_substr($text, 0, 4000, 'UTF-8'));
    tgs_notify_operator_new_message($pdo, $chatId, $from);
    tgs_send_user_ack($chatId);

    tgs_json_response(['ok' => true]);
} catch (Throwable $e) {
    error_log('Telegram support webhook error: ' . $e->getMessage());
    tgs_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
