<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/api/auth/require_admin.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../telegram/lib.php';
$hasSupportLib = is_file(__DIR__ . '/../../telegram_support/lib.php');
if ($hasSupportLib) {
    require_once __DIR__ . '/../../telegram_support/lib.php';
}

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
    foreach ([
        'query' => 'Запрос',
        'barcode' => 'Штрих-код',
        'article' => 'Артикул',
        'product_name' => 'Товар',
        'count' => 'Количество',
        'callback' => 'Кнопка',
        'text' => 'Текст',
    ] as $key => $label) {
        if (!array_key_exists($key, $payload)) {
            continue;
        }
        $value = trim((string)$payload[$key]);
        if ($value === '') {
            continue;
        }
        $parts[] = $label . ': ' . $value;
    }

    return empty($parts) ? '' : implode(' | ', $parts);
}

function tg_support_preview(?string $text): string
{
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }
    $text = preg_replace('~\s+~u', ' ', $text) ?? $text;
    if (mb_strlen($text, 'UTF-8') > 160) {
        return mb_substr($text, 0, 157, 'UTF-8') . '...';
    }
    return $text;
}

try {
    tg_ensure_tables($pdo);

    $selectedChatId = trim((string)($_GET['chat_id'] ?? ''));
    if ($selectedChatId !== '' && !preg_match('~^-?\d+$~', $selectedChatId)) {
        throw new RuntimeException('Некорректный chat_id');
    }

    $selectedSupportChatId = trim((string)($_GET['support_chat_id'] ?? ''));
    if ($selectedSupportChatId !== '' && !preg_match('~^-?\d+$~', $selectedSupportChatId)) {
        throw new RuntimeException('Некорректный support_chat_id');
    }

    $stats = [
        'total_users' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_user_states')->fetchColumn(),
        'accepted_users' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_user_states WHERE consent_accepted = 1')->fetchColumn(),
        'pending_users' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_user_states WHERE consent_accepted = 0')->fetchColumn(),
        'actions_total' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_activity_log')->fetchColumn(),
        'actions_24h' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_activity_log WHERE created_at >= (NOW() - INTERVAL 1 DAY)')->fetchColumn(),
    ];

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
        LIMIT 500
    ";
    $users = $pdo->query($usersSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($users as &$user) {
        $user['details_text'] = tg_admin_payload_text($user['last_payload'] ?? null);
        unset($user['last_payload']);
    }
    unset($user);

    $selectedUser = null;
    if ($selectedChatId !== '') {
        foreach ($users as $user) {
            if ((string)($user['chat_id'] ?? '') === $selectedChatId) {
                $selectedUser = $user;
                break;
            }
        }
        if ($selectedUser === null) {
            $userSt = $pdo->prepare("SELECT chat_id, user_id, username, first_name, last_name, state, created_at, updated_at, consent_accepted, consent_at, last_action, last_action_label, last_payload, (SELECT COUNT(*) FROM telegram_activity_log l WHERE l.chat_id = s.chat_id) AS total_actions FROM telegram_user_states s WHERE chat_id = ? LIMIT 1");
            $userSt->execute([$selectedChatId]);
            $selectedUser = $userSt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (is_array($selectedUser)) {
                $selectedUser['details_text'] = tg_admin_payload_text($selectedUser['last_payload'] ?? null);
                unset($selectedUser['last_payload']);
            }
        }
    }

    if ($selectedChatId !== '') {
        $actionsSt = $pdo->prepare(
            "SELECT id, chat_id, user_id, username, first_name, last_name, action_code, action_label, payload_json, created_at
             FROM telegram_activity_log
             WHERE chat_id = ?
             ORDER BY id DESC
             LIMIT 1000"
        );
        $actionsSt->execute([$selectedChatId]);
        $actions = $actionsSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $actions = $pdo->query(
            "SELECT id, chat_id, user_id, username, first_name, last_name, action_code, action_label, payload_json, created_at
             FROM telegram_activity_log
             ORDER BY id DESC
             LIMIT 200"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    foreach ($actions as &$action) {
        $action['details_text'] = tg_admin_payload_text($action['payload_json'] ?? null);
        unset($action['payload_json']);
    }
    unset($action);

    $supportStats = [
        'total_threads' => 0,
        'unread_threads' => 0,
        'unread_messages' => 0,
        'messages_total' => 0,
    ];
    $supportThreads = [];
    $selectedSupportThread = null;
    $supportMessages = [];

    if ($hasSupportLib) {
        tgs_ensure_tables($pdo);

        if ($selectedSupportChatId !== '') {
            tgs_mark_thread_as_read($pdo, $selectedSupportChatId);
        }

        $supportStats = [
            'total_threads' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_support_threads')->fetchColumn(),
            'unread_threads' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_support_threads WHERE unread_count > 0')->fetchColumn(),
            'unread_messages' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_support_messages WHERE is_read = 0')->fetchColumn(),
            'messages_total' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_support_messages')->fetchColumn(),
        ];

        $supportThreads = $pdo->query(
            "SELECT chat_id, user_id, username, first_name, last_name, last_message_text, last_message_at, last_user_message_at, unread_count, created_at, updated_at
             FROM telegram_support_threads
             ORDER BY unread_count DESC, COALESCE(last_user_message_at, last_message_at, updated_at) DESC, chat_id DESC
             LIMIT 500"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($supportThreads as &$thread) {
            $thread['last_message_preview'] = tg_support_preview($thread['last_message_text'] ?? '');
        }
        unset($thread);

        if ($selectedSupportChatId !== '') {
            foreach ($supportThreads as $thread) {
                if ((string)($thread['chat_id'] ?? '') === $selectedSupportChatId) {
                    $selectedSupportThread = $thread;
                    break;
                }
            }
            if ($selectedSupportThread === null) {
                $st = $pdo->prepare('SELECT chat_id, user_id, username, first_name, last_name, last_message_text, last_message_at, last_user_message_at, unread_count, created_at, updated_at FROM telegram_support_threads WHERE chat_id = ? LIMIT 1');
                $st->execute([$selectedSupportChatId]);
                $selectedSupportThread = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                if (is_array($selectedSupportThread)) {
                    $selectedSupportThread['last_message_preview'] = tg_support_preview($selectedSupportThread['last_message_text'] ?? '');
                }
            }

            $st = $pdo->prepare(
                'SELECT id, chat_id, user_id, username, first_name, last_name, direction, message_text, is_read, created_at
                 FROM telegram_support_messages
                 WHERE chat_id = ?
                 ORDER BY id DESC
                 LIMIT 500'
            );
            $st->execute([$selectedSupportChatId]);
            $supportMessages = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    echo json_encode([
        'ok' => true,
        'stats' => $stats,
        'users' => $users,
        'selected_chat_id' => $selectedChatId,
        'selected_user' => $selectedUser,
        'actions' => $actions,
        'support_stats' => $supportStats,
        'support_threads' => $supportThreads,
        'selected_support_chat_id' => $selectedSupportChatId,
        'selected_support_thread' => $selectedSupportThread,
        'support_messages' => $supportMessages,
        'support_enabled' => $hasSupportLib,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
