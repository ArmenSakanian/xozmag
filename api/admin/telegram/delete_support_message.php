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

    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $messageId = (int)($data['message_id'] ?? 0);
    if ($messageId <= 0) {
        throw new RuntimeException('Некорректный message_id');
    }

    $result = tgs_delete_message_everywhere($pdo, $messageId);

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
