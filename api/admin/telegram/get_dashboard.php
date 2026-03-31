<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../telegram/lib.php';

function tg_admin_payload_text(?string $payloadJson): string
{
    if ($payloadJson === null || trim($payloadJson) === '') {
        return '';
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return '';
    }

    $parts = [];
    foreach (['query' => 'Запрос', 'barcode' => 'Штрих-код', 'article' => 'Артикул', 'product_name' => 'Товар', 'count' => 'Количество', 'callback' => 'Кнопка', 'text' => 'Текст'] as $key => $label) {
        if (!array_key_exists($key, $payload)) {
            continue;
        }
        $value = trim((string)$payload[$key]);
        if ($value === '') {
            continue;
        }
        $parts[] = $label . ': ' . $value;
    }

    if (empty($parts)) {
        return '';
    }

    return implode(' | ', $parts);
}

try {
    tg_ensure_tables($pdo);

    $stats = [];
    $stats['total_users'] = (int)$pdo->query('SELECT COUNT(*) FROM telegram_user_states')->fetchColumn();
    $stats['accepted_users'] = (int)$pdo->query('SELECT COUNT(*) FROM telegram_user_states WHERE consent_accepted = 1')->fetchColumn();
    $stats['pending_users'] = (int)$pdo->query('SELECT COUNT(*) FROM telegram_user_states WHERE consent_accepted = 0')->fetchColumn();
    $stats['actions_total'] = (int)$pdo->query('SELECT COUNT(*) FROM telegram_activity_log')->fetchColumn();
    $stats['actions_24h'] = (int)$pdo->query('SELECT COUNT(*) FROM telegram_activity_log WHERE created_at >= (NOW() - INTERVAL 1 DAY)')->fetchColumn();

    $usersSql = "
        SELECT
            s.chat_id,
            s.user_id,
            s.username,
            s.first_name,
            s.last_name,
            s.state,
            s.created_at,
            s.updated_at,
            s.consent_accepted,
            s.consent_at,
            s.last_action,
            s.last_action_label,
            s.last_payload,
            (
                SELECT COUNT(*)
                FROM telegram_activity_log l
                WHERE l.chat_id = s.chat_id
            ) AS total_actions
        FROM telegram_user_states s
        ORDER BY s.updated_at DESC, s.chat_id DESC
        LIMIT 300
    ";
    $users = $pdo->query($usersSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($users as &$user) {
        $user['details_text'] = tg_admin_payload_text($user['last_payload'] ?? null);
        unset($user['last_payload']);
    }
    unset($user);

    $actionsSql = "
        SELECT
            id,
            chat_id,
            user_id,
            username,
            first_name,
            last_name,
            action_code,
            action_label,
            payload_json,
            created_at
        FROM telegram_activity_log
        ORDER BY id DESC
        LIMIT 500
    ";
    $actions = $pdo->query($actionsSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($actions as &$action) {
        $action['details_text'] = tg_admin_payload_text($action['payload_json'] ?? null);
        unset($action['payload_json']);
    }
    unset($action);

    echo json_encode([
        'ok' => true,
        'stats' => $stats,
        'users' => $users,
        'actions' => $actions,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
