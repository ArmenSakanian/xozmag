<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../telegram_support/lib.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Метод не поддерживается');
    }

    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            $data = [];
        }
    } else {
        $data = $_POST;
    }

    $conversationId = (int)($data['conversation_id'] ?? 0);
    if ($conversationId <= 0) {
        $chatId = trim((string)($data['chat_id'] ?? ''));
        if ($chatId !== '' && preg_match('~^-?\d+$~', $chatId)) {
            $conversation = tgs_get_active_conversation_by_chat_id($pdo, $chatId) ?? tgs_get_latest_conversation_by_chat_id($pdo, $chatId);
            $conversationId = (int)($conversation['id'] ?? 0);
        }
    }

    $messageText = trim((string)($data['message_text'] ?? ''));
    $photo = $_FILES['photo'] ?? null;

    if ($conversationId <= 0) {
        throw new RuntimeException('Некорректный conversation_id');
    }

    $hasPhoto = is_array($photo) && (int)($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($messageText === '' && !$hasPhoto) {
        throw new RuntimeException('Введите текст ответа или прикрепите фотографию');
    }

    if ($hasPhoto) {
        $result = tgs_send_admin_photo_reply($pdo, $conversationId, $photo, $messageText);
    } else {
        $messageText = mb_substr($messageText, 0, 4000, 'UTF-8');
        $result = tgs_send_admin_reply($pdo, $conversationId, $messageText);
    }

    echo json_encode([
        'ok' => true,
        'result' => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
