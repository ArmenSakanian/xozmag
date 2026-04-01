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
    $caption = trim((string)($message['caption'] ?? ''));
    $hasPhoto = !empty($message['photo']) && is_array($message['photo']);

    if ($chatId === null) {
        tgs_json_response(['ok' => true]);
    }

    if ($chatType !== 'private') {
        tgs_send_message($chatId, 'Этот бот работает только в личном диалоге. Откройте чат с ботом и нажмите /start.');
        tgs_json_response(['ok' => true]);
    }

    $isOperator = tgs_is_operator($pdo, $chatId, $from);

    if ($text === '/start' || str_starts_with($text, '/start ')) {
        try {
            if ($isOperator) {
                tgs_send_operator_welcome($chatId);
            } else {
                tgs_send_user_welcome($chatId);
            }
        } catch (Throwable $e) {
            error_log('Telegram support start reply failed: ' . $e->getMessage());
        }
        tgs_json_response(['ok' => true]);
    }

    if (($text === '' && !$hasPhoto) || ($text !== '' && str_starts_with($text, '/'))) {
        try {
            if ($isOperator) {
                tgs_send_message($chatId, 'Уведомления подключены. Новые обращения пользователей будут приходить в этот чат.');
            } else {
                tgs_send_message($chatId, 'Напишите Ваш вопрос одним сообщением или отправьте фотографию с подписью. Сотрудник магазина ознакомится с обращением как можно скорее.');
            }
        } catch (Throwable $e) {
            error_log('Telegram support helper reply failed: ' . $e->getMessage());
        }
        tgs_json_response(['ok' => true]);
    }

    if ($isOperator) {
        try {
            tgs_send_message($chatId, 'Этот бот используется для уведомлений о новых обращениях и приёма сообщений от клиентов. Историю обращений просматривайте в админ-панели сайта.');
        } catch (Throwable $e) {
            error_log('Telegram support operator helper reply failed: ' . $e->getMessage());
        }
        tgs_json_response(['ok' => true]);
    }

    if ($hasPhoto || $text !== '' || $caption !== '') {
        $conversation = tgs_store_incoming_message($pdo, $chatId, $from, $message);
        tgs_acknowledge_conversation_if_needed($pdo, (int)$conversation['id']);
        tgs_notify_operator_new_message($pdo, $conversation, $from);
    }

    tgs_json_response(['ok' => true]);
} catch (Throwable $e) {
    error_log('Telegram support webhook error: ' . $e->getMessage());
    tgs_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
