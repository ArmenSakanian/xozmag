<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../telegram/lib.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Метод не поддерживается');
    }

    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $chatIds = $data['chat_ids'] ?? [];
    if (!is_array($chatIds) || $chatIds === []) {
        throw new RuntimeException('Не выбраны пользователи для удаления');
    }

    $normalized = [];
    foreach ($chatIds as $chatId) {
        $chatId = trim((string)$chatId);
        if ($chatId === '' || !preg_match('~^-?\d+$~', $chatId)) {
            continue;
        }
        $normalized[$chatId] = $chatId;
    }
    $normalized = array_values($normalized);

    if ($normalized === []) {
        throw new RuntimeException('Не выбраны корректные chat_id');
    }

    tg_ensure_tables($pdo);

    $placeholders = implode(',', array_fill(0, count($normalized), '?'));

    $pdo->beginTransaction();
    try {
        $deleteActions = $pdo->prepare("DELETE FROM telegram_activity_log WHERE chat_id IN ($placeholders)");
        $deleteActions->execute($normalized);

        $deleteUsers = $pdo->prepare("DELETE FROM telegram_user_states WHERE chat_id IN ($placeholders)");
        $deleteUsers->execute($normalized);
        $deletedUsers = (int)$deleteUsers->rowCount();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    echo json_encode([
        'ok' => true,
        'deleted_users' => $deletedUsers,
        'chat_ids' => $normalized,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
