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

function tg_support_preview(?string $text, string $contentType = 'text'): string
{
    $text = trim((string)$text);
    if ($contentType === 'photo') {
        if ($text === '' || mb_strtolower($text, 'UTF-8') === 'фотография') {
            return 'Фотография';
        }
        return 'Фотография - ' . $text;
    }
    if ($text === '') {
        return '';
    }
    $text = preg_replace('~\s+~u', ' ', $text) ?? $text;
    if (mb_strlen($text, 'UTF-8') > 160) {
        return mb_substr($text, 0, 157, 'UTF-8') . '...';
    }
    return $text;
}


function tg_normalize_support_thread(array $thread): array
{
    if (($thread['status'] ?? '') === 'archived') {
        $thread['status'] = 'closed';
    }
    return $thread;
}

function tg_support_topic_label(array $thread): string
{
    $sourceType = trim((string)($thread['source_type'] ?? ''));
    $productName = trim((string)($thread['source_product_name'] ?? ''));
    if ($sourceType === 'purchase') {
        return $productName !== '' ? 'Покупка или доставка - ' . $productName : 'Покупка или доставка';
    }
    return $productName;
}

function tg_support_topic_details(array $thread): string
{
    $parts = [];
    $article = trim((string)($thread['source_product_article'] ?? ''));
    $barcode = trim((string)($thread['source_product_barcode'] ?? ''));
    if ($article !== '') {
        $parts[] = 'Артикул: ' . $article;
    }
    if ($barcode !== '') {
        $parts[] = 'Штрих-код: ' . $barcode;
    }
    return implode(' - ', $parts);
}

function tg_support_message_media_url(array $message): ?string
{
    if (!empty($message['media_path']) || !empty($message['media_file_id'])) {
        return '/api/admin/telegram/support_media.php?message_id=' . rawurlencode((string)($message['id'] ?? ''));
    }
    return null;
}

function tg_normalize_support_messages(array $messages): array
{
    foreach ($messages as &$message) {
        $message['media_url'] = tg_support_message_media_url($message);
    }
    unset($message);
    return $messages;
}

try {
    tg_ensure_tables($pdo);

    $selectedChatId = trim((string)($_GET['chat_id'] ?? ''));
    if ($selectedChatId !== '' && !preg_match('~^-?\d+$~', $selectedChatId)) {
        throw new RuntimeException('Некорректный chat_id');
    }

    $selectedSupportThreadId = (int)($_GET['support_thread_id'] ?? 0);
    $selectedSupportChatId = trim((string)($_GET['support_chat_id'] ?? ''));
    $markSupportRead = (int)($_GET['mark_support_read'] ?? 0) === 1;
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

    $actions = [];
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

        foreach ($actions as &$action) {
            $action['details_text'] = tg_admin_payload_text($action['payload_json'] ?? null);
            unset($action['payload_json']);
        }
        unset($action);
    }

    $supportStats = [
        'total_threads' => 0,
        'unread_threads' => 0,
        'unread_messages' => 0,
        'messages_total' => 0,
        'waiting_replies' => 0,
        'active_threads' => 0,
        'closed_threads' => 0,
        'archived_threads' => 0,
    ];
    $supportThreads = [];
    $selectedSupportThread = null;
    $supportMessages = [];
    $previousSupportCycles = [];

    if ($hasSupportLib) {
        tgs_ensure_tables($pdo);

        if ($selectedSupportThreadId <= 0 && $selectedSupportChatId !== '') {
            $selectedByChat = tgs_get_latest_conversation_by_chat_id($pdo, $selectedSupportChatId);
            if (is_array($selectedByChat)) {
                $selectedSupportThreadId = (int)$selectedByChat['id'];
            }
        }

        if ($selectedSupportThreadId > 0 && $markSupportRead) {
            tgs_mark_conversation_as_read($pdo, $selectedSupportThreadId);
        }

        $pdo->exec("UPDATE telegram_support_conversations SET status = 'closed', archived_at = NULL, closed_at = COALESCE(closed_at, NOW()), updated_at = CURRENT_TIMESTAMP WHERE deleted_at IS NULL AND status = 'archived'");

        $supportStats = [
            'total_threads' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_support_conversations WHERE deleted_at IS NULL')->fetchColumn(),
            'unread_threads' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_support_conversations WHERE deleted_at IS NULL AND unread_count > 0')->fetchColumn(),
            'unread_messages' => (int)$pdo->query("SELECT COUNT(*) FROM telegram_support_messages WHERE deleted_at IS NULL AND direction = 'incoming' AND is_read = 0")->fetchColumn(),
            'messages_total' => (int)$pdo->query('SELECT COUNT(*) FROM telegram_support_messages WHERE deleted_at IS NULL')->fetchColumn(),
            'waiting_replies' => (int)$pdo->query("SELECT COUNT(*) FROM telegram_support_conversations WHERE deleted_at IS NULL AND status = 'active' AND last_user_message_at IS NOT NULL AND (last_admin_message_at IS NULL OR last_user_message_at > last_admin_message_at)")->fetchColumn(),
            'active_threads' => (int)$pdo->query("SELECT COUNT(*) FROM telegram_support_conversations WHERE deleted_at IS NULL AND status = 'active'")->fetchColumn(),
            'closed_threads' => (int)$pdo->query("SELECT COUNT(*) FROM telegram_support_conversations WHERE deleted_at IS NULL AND status = 'closed'")->fetchColumn(),
            'archived_threads' => 0,
        ];

        $supportThreads = $pdo->query(
            "SELECT id, chat_id, user_id, username, first_name, last_name, status, source_type, source_product_id, source_product_name, source_product_article, source_product_barcode, last_message_text, last_message_at, last_user_message_at, last_admin_message_at, unread_count, ack_sent, created_at, updated_at, closed_at, archived_at
             FROM telegram_support_conversations
             WHERE deleted_at IS NULL
             ORDER BY CASE status WHEN 'active' THEN 0 ELSE 1 END, unread_count DESC, COALESCE(last_user_message_at, last_message_at, updated_at) DESC, id DESC
             LIMIT 1000"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $conversationIds = array_map(static fn(array $thread): int => (int)$thread['id'], $supportThreads);
        $conversationMessageTypeMap = [];
        if (!empty($conversationIds)) {
            $placeholders = implode(',', array_fill(0, count($conversationIds), '?'));
            $typeSt = $pdo->prepare(
                "SELECT m.conversation_id, m.content_type
                 FROM telegram_support_messages m
                 INNER JOIN (
                    SELECT conversation_id, MAX(id) AS max_id
                    FROM telegram_support_messages
                    WHERE deleted_at IS NULL AND conversation_id IN ($placeholders)
                    GROUP BY conversation_id
                 ) latest ON latest.max_id = m.id"
            );
            $typeSt->execute($conversationIds);
            foreach ($typeSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $conversationMessageTypeMap[(int)$row['conversation_id']] = (string)($row['content_type'] ?? 'text');
            }
        }

        foreach ($supportThreads as &$thread) {
            $thread = tg_normalize_support_thread($thread);
            $thread['last_message_preview'] = tg_support_preview($thread['last_message_text'] ?? '', $conversationMessageTypeMap[(int)$thread['id']] ?? 'text');
            $thread['needs_reply'] = tgs_conversation_needs_reply($thread);
            $thread['topic_label'] = tg_support_topic_label($thread);
            $thread['topic_details'] = tg_support_topic_details($thread);
        }
        unset($thread);

        if ($selectedSupportThreadId > 0) {
            foreach ($supportThreads as $thread) {
                if ((int)($thread['id'] ?? 0) === $selectedSupportThreadId) {
                    $selectedSupportThread = $thread;
                    break;
                }
            }
            if ($selectedSupportThread === null) {
                $selectedSupportThread = tgs_get_conversation($pdo, $selectedSupportThreadId);
                if (is_array($selectedSupportThread)) {
                    $selectedSupportThread = tg_normalize_support_thread($selectedSupportThread);
                    $selectedSupportThread['last_message_preview'] = tg_support_preview($selectedSupportThread['last_message_text'] ?? '');
                    $selectedSupportThread['needs_reply'] = tgs_conversation_needs_reply($selectedSupportThread);
                    $selectedSupportThread['topic_label'] = tg_support_topic_label($selectedSupportThread);
                    $selectedSupportThread['topic_details'] = tg_support_topic_details($selectedSupportThread);
                }
            }

            if ($selectedSupportThread !== null) {
                $st = $pdo->prepare(
                    'SELECT id, conversation_id, chat_id, user_id, username, first_name, last_name, direction, content_type, message_text, is_read, telegram_message_id, media_file_id, media_path, media_mime, edited_at, deleted_at, deleted_for_all, created_at
                     FROM telegram_support_messages
                     WHERE conversation_id = ?
                     ORDER BY id ASC
                     LIMIT 2000'
                );
                $st->execute([$selectedSupportThreadId]);
                $supportMessages = tg_normalize_support_messages($st->fetchAll(PDO::FETCH_ASSOC) ?: []);

                $previousCyclesSt = $pdo->prepare(
                    "SELECT id, chat_id, user_id, username, first_name, last_name, status, source_type, source_product_id, source_product_name, source_product_article, source_product_barcode, last_message_text, last_message_at, last_user_message_at, last_admin_message_at, unread_count, ack_sent, created_at, updated_at, closed_at, archived_at
                     FROM telegram_support_conversations
                     WHERE deleted_at IS NULL AND chat_id = ? AND id <> ? AND status = 'closed' AND id < ?
                     ORDER BY id ASC"
                );
                $previousCyclesSt->execute([
                    (string)($selectedSupportThread['chat_id'] ?? ''),
                    $selectedSupportThreadId,
                    $selectedSupportThreadId,
                ]);
                $previousSupportCycles = $previousCyclesSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                if (!empty($previousSupportCycles)) {
                    $previousConversationIds = array_map(static fn(array $thread): int => (int)$thread['id'], $previousSupportCycles);
                    $placeholders = implode(',', array_fill(0, count($previousConversationIds), '?'));
                    $messagesSt = $pdo->prepare(
                        "SELECT id, conversation_id, chat_id, user_id, username, first_name, last_name, direction, content_type, message_text, is_read, telegram_message_id, media_file_id, media_path, media_mime, edited_at, deleted_at, deleted_for_all, created_at
                         FROM telegram_support_messages
                         WHERE conversation_id IN ($placeholders)
                         ORDER BY conversation_id ASC, id ASC"
                    );
                    $messagesSt->execute($previousConversationIds);
                    $previousMessages = $messagesSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    $previousMessages = tg_normalize_support_messages($previousMessages);

                    $previousMessagesMap = [];
                    foreach ($previousMessages as $message) {
                        $conversationId = (int)($message['conversation_id'] ?? 0);
                        if (!isset($previousMessagesMap[$conversationId])) {
                            $previousMessagesMap[$conversationId] = [];
                        }
                        $previousMessagesMap[$conversationId][] = $message;
                    }

                    foreach ($previousSupportCycles as &$cycle) {
                        $cycle = tg_normalize_support_thread($cycle);
                        $cycle['last_message_preview'] = tg_support_preview($cycle['last_message_text'] ?? '');
                        $cycle['needs_reply'] = false;
                        $cycle['topic_label'] = tg_support_topic_label($cycle);
                        $cycle['topic_details'] = tg_support_topic_details($cycle);
                        $cycle['messages'] = $previousMessagesMap[(int)$cycle['id']] ?? [];
                    }
                    unset($cycle);
                }
            }
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
        'selected_support_thread_id' => $selectedSupportThreadId,
        'selected_support_thread' => $selectedSupportThread,
        'support_messages' => $supportMessages,
        'previous_support_cycles' => $previousSupportCycles,
        'support_enabled' => $hasSupportLib,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
