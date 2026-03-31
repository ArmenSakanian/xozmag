<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$secret = tg_webhook_secret();
if ($secret !== '') {
    $headerSecret = trim((string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''));
    if (!hash_equals($secret, $headerSecret)) {
        tg_json_response(['ok' => false, 'error' => 'forbidden'], 403);
    }
}

$raw = file_get_contents('php://input');
$update = json_decode((string)$raw, true);
if (!is_array($update)) {
    tg_json_response(['ok' => true]);
}

try {
    global $pdo;

    if (!empty($update['callback_query']) && is_array($update['callback_query'])) {
        $callback = $update['callback_query'];
        $callbackId = (string)($callback['id'] ?? '');
        $data = trim((string)($callback['data'] ?? ''));
        $message = $callback['message'] ?? [];
        $chatId = $message['chat']['id'] ?? null;
        $from = is_array($callback['from'] ?? null) ? $callback['from'] : [];

        if ($callbackId !== '') {
            tg_answer_callback($callbackId);
        }
        if ($chatId === null) {
            tg_json_response(['ok' => true]);
        }

        if ($data === 'menu') {
            tg_clear_user_state($pdo, $chatId, $from);
            tg_send_main_menu($chatId);
            tg_json_response(['ok' => true]);
        }

        if (preg_match('~^product:(\d+)$~', $data, $m)) {
            $product = tg_fetch_product_by_id($pdo, (int)$m[1]);
            if ($product) {
                tg_clear_user_state($pdo, $chatId, $from);
                tg_send_product_card($chatId, $product);
            } else {
                tg_send_message($chatId, 'Товар не найден. Возможно, он был удалён или временно скрыт.', [
                    'reply_markup' => tg_reply_main_keyboard(),
                ]);
            }
            tg_json_response(['ok' => true]);
        }

        tg_json_response(['ok' => true]);
    }

    $message = $update['message'] ?? null;
    if (!is_array($message)) {
        tg_json_response(['ok' => true]);
    }

    $chat = $message['chat'] ?? [];
    $chatId = $chat['id'] ?? null;
    $chatType = (string)($chat['type'] ?? '');
    $from = is_array($message['from'] ?? null) ? $message['from'] : [];
    $text = trim((string)($message['text'] ?? ''));

    if ($chatId === null) {
        tg_json_response(['ok' => true]);
    }

    if ($chatType !== 'private') {
        tg_send_message($chatId, 'Этот бот работает только в личном диалоге. Откройте чат с ботом и нажмите /start.');
        tg_json_response(['ok' => true]);
    }

    $stateRow = tg_get_user_state($pdo, $chatId);
    $state = (string)($stateRow['state'] ?? '');

    if ($text === '/start' || $text === '/menu' || $text === '/help') {
        tg_clear_user_state($pdo, $chatId, $from);
        tg_send_main_menu($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($text === 'Назад в меню') {
        tg_clear_user_state($pdo, $chatId, $from);
        tg_send_main_menu($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($text === 'Связаться с магазином') {
        tg_clear_user_state($pdo, $chatId, $from);
        tg_send_contacts($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($text === 'Найти товар') {
        tg_clear_user_state($pdo, $chatId, $from);
        tg_send_search_menu($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($text === 'По названию') {
        tg_set_user_state($pdo, $chatId, $from, 'wait_name');
        tg_send_message($chatId, 'Укажите название товара одним сообщением.', [
            'reply_markup' => tg_reply_back_only_keyboard(),
        ]);
        tg_json_response(['ok' => true]);
    }

    if ($text === 'По штрих-коду') {
        tg_set_user_state($pdo, $chatId, $from, 'wait_barcode');
        tg_send_message($chatId, 'Укажите штрих-код товара одним сообщением.', [
            'reply_markup' => tg_reply_back_only_keyboard(),
        ]);
        tg_json_response(['ok' => true]);
    }

    if ($text === 'По артикулу') {
        tg_set_user_state($pdo, $chatId, $from, 'wait_article');
        tg_send_message($chatId, 'Укажите артикул товара одним сообщением.', [
            'reply_markup' => tg_reply_back_only_keyboard(),
        ]);
        tg_json_response(['ok' => true]);
    }

    if ($text === '') {
        tg_send_message($chatId, 'Отправьте текстовое сообщение или воспользуйтесь кнопками ниже.', [
            'reply_markup' => in_array($state, ['wait_name', 'wait_barcode', 'wait_article'], true)
                ? tg_reply_back_only_keyboard()
                : tg_reply_main_keyboard(),
        ]);
        tg_json_response(['ok' => true]);
    }

    if ($state === 'wait_name') {
        $limit = max(10, (int)(tg_config()['search_results_limit'] ?? 10));
        $products = tg_search_products_by_name($pdo, $text, $limit);
        if (empty($products)) {
            tg_send_message($chatId, 'По указанному запросу товары не найдены. Уточните название и отправьте его ещё раз.', [
                'reply_markup' => tg_reply_back_only_keyboard(),
            ]);
            tg_json_response(['ok' => true]);
        }

        tg_clear_user_state($pdo, $chatId, $from);
        if (count($products) === 1) {
            tg_send_product_card($chatId, $products[0]);
        } else {
            tg_send_product_choices($chatId, $products, $text);
        }
        tg_json_response(['ok' => true]);
    }

    if ($state === 'wait_barcode') {
        $product = tg_find_product_by_barcode($pdo, $text);
        if (!$product) {
            tg_send_message($chatId, 'Товар с таким штрих-кодом не найден. Проверьте код и отправьте его ещё раз.', [
                'reply_markup' => tg_reply_back_only_keyboard(),
            ]);
            tg_json_response(['ok' => true]);
        }

        tg_clear_user_state($pdo, $chatId, $from);
        tg_send_product_card($chatId, $product);
        tg_json_response(['ok' => true]);
    }

    if ($state === 'wait_article') {
        $limit = max(10, (int)(tg_config()['search_results_limit'] ?? 10));
        $products = tg_search_products_by_article($pdo, $text, $limit);
        if (empty($products)) {
            tg_send_message($chatId, 'Товар с таким артикулом не найден. Проверьте артикул и отправьте его ещё раз.', [
                'reply_markup' => tg_reply_back_only_keyboard(),
            ]);
            tg_json_response(['ok' => true]);
        }

        tg_clear_user_state($pdo, $chatId, $from);
        if (count($products) === 1) {
            tg_send_product_card($chatId, $products[0]);
        } else {
            tg_send_product_choices($chatId, $products, $text);
        }
        tg_json_response(['ok' => true]);
    }

    tg_send_main_menu($chatId, 'Выберите действие с помощью кнопок ниже.');
    tg_json_response(['ok' => true]);
} catch (Throwable $e) {
    error_log('Telegram webhook error: ' . $e->getMessage());
    tg_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
