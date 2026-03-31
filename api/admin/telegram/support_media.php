<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../telegram_support/lib.php';

try {
    $messageId = (int)($_GET['message_id'] ?? 0);
    if ($messageId <= 0) {
        throw new RuntimeException('Некорректный message_id');
    }

    $message = tgs_get_message($pdo, $messageId);
    if (!$message || !empty($message['deleted_at'])) {
        throw new RuntimeException('Файл не найден');
    }

    $mime = trim((string)($message['media_mime'] ?? ''));
    $localPath = trim((string)($message['media_path'] ?? ''));
    if ($localPath !== '') {
        $absolute = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($localPath, '/');
        if (!is_file($absolute)) {
            $absolute = dirname(__DIR__, 3) . '/' . ltrim($localPath, '/');
        }
        if (!is_file($absolute)) {
            throw new RuntimeException('Файл не найден на сервере');
        }
        if ($mime === '') {
            $mime = tgs_guess_mime_from_extension($absolute);
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($absolute));
        readfile($absolute);
        exit;
    }

    $fileId = trim((string)($message['media_file_id'] ?? ''));
    if ($fileId === '') {
        throw new RuntimeException('У файла нет media_file_id');
    }

    $filePath = tgs_get_file_path($fileId);
    $bytes = tgs_download_file_bytes($filePath);
    if ($mime === '') {
        $mime = tgs_guess_mime_from_extension($filePath);
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . strlen($bytes));
    echo $bytes;
    exit;
} catch (Throwable $e) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
