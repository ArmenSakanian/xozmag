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

    $isText = static function (string $value, array $variants): bool {
        return in_array($value, $variants, true);
    };

    if (!empty($update['callback_query']) && is_array($update['callback_query'])) {
        $callback = $update['callback_query'];
        $callbackId = (string)($callback['id'] ?? '');
        $data = trim((string)($callback['data'] ?? ''));
        $message = $callback['message'] ?? [];
        $chatId = $message['chat']['id'] ?? null;
        $messageId = $message['message_id'] ?? null;
        $from = is_array($callback['from'] ?? null) ? $callback['from'] : [];

        if ($chatId === null) {
            tg_json_response(['ok' => true]);
        }

        $stateRow = tg_get_user_state($pdo, $chatId);
        tg_touch_user(
            $pdo,
            $chatId,
            $from,
            (string)($stateRow['state'] ?? ''),
            is_array($stateRow['payload'] ?? null) ? $stateRow['payload'] : null
        );

        if ($data === 'consent:accept') {
            if (tg_user_has_consent($pdo, $chatId)) {
                if ($messageId !== null) {
                    tg_edit_message_reply_markup($chatId, $messageId);
                }
                if ($callbackId !== '') {
                    tg_answer_callback($callbackId, 'Политика конфиденциальности уже принята.');
                }
                tg_json_response(['ok' => true]);
            }

            tg_accept_user_consent($pdo, $chatId, $from);
            tg_clear_user_state($pdo, $chatId, $from);
            tg_log_action($pdo, $chatId, $from, 'consent_accept', 'Пользователь принял политику конфиденциальности');
            if ($messageId !== null) {
                tg_edit_message_reply_markup($chatId, $messageId);
            }
            if ($callbackId !== '') {
                tg_answer_callback($callbackId, 'Политика конфиденциальности принята.');
            }
            tg_send_main_menu($chatId, "Благодарим. Политика конфиденциальности принята.

Теперь вы можете пользоваться ботом.");
            tg_json_response(['ok' => true]);
        }

        if ($data === 'consent:decline') {
            tg_decline_user_consent($pdo, $chatId, $from);
            tg_clear_user_state($pdo, $chatId, $from);
            tg_log_action($pdo, $chatId, $from, 'consent_decline', 'Пользователь отказался от политики конфиденциальности');
            if ($messageId !== null) {
                tg_edit_message_reply_markup($chatId, $messageId);
            }
            if ($callbackId !== '') {
                tg_answer_callback($callbackId);
            }
            tg_send_consent_declined_message($chatId);
            tg_json_response(['ok' => true]);
        }

        if (!tg_user_has_consent($pdo, $chatId)) {
            tg_log_action($pdo, $chatId, $from, 'consent_required', 'Попытка использовать бота без принятия политики', [
                'callback' => $data,
            ]);
            if ($callbackId !== '') {
                tg_answer_callback($callbackId);
            }
            tg_send_consent_prompt($chatId);
            tg_json_response(['ok' => true]);
        }

        if ($data === 'menu') {
            tg_clear_user_state($pdo, $chatId, $from);
            tg_log_action($pdo, $chatId, $from, 'open_main_menu', 'Открыл главное меню');
            if ($callbackId !== '') {
                tg_answer_callback($callbackId);
            }
            tg_send_main_menu($chatId);
            tg_json_response(['ok' => true]);
        }

        if ($data === 'search_menu') {
            tg_clear_user_state($pdo, $chatId, $from);
            tg_log_action($pdo, $chatId, $from, 'open_search_menu', 'Открыл меню поиска');
            if ($callbackId !== '') {
                tg_answer_callback($callbackId);
            }
            tg_send_search_menu($chatId, 'Выберите способ поиска товара.');
            tg_json_response(['ok' => true]);
        }

        if (preg_match('~^product:(\d+)$~', $data, $m)) {
            $product = tg_fetch_product_by_id($pdo, (int)$m[1]);
            if ($product) {
                tg_clear_user_state($pdo, $chatId, $from);
                tg_log_action($pdo, $chatId, $from, 'view_product', 'Открыл карточку товара из списка', [
                    'product_id' => (int)$product['id'],
                    'product_name' => (string)($product['name'] ?? ''),
                ]);
                if ($callbackId !== '') {
                    tg_answer_callback($callbackId);
                }
                tg_send_product_card($chatId, $product);
            } else {
                if ($callbackId !== '') {
                    tg_answer_callback($callbackId);
                }
                tg_send_message($chatId, 'Товар не найден. Возможно, он был удалён или временно скрыт.', [
                    'reply_markup' => tg_reply_main_keyboard(),
                ]);
            }
            tg_json_response(['ok' => true]);
        }

        if ($callbackId !== '') {
            tg_answer_callback($callbackId);
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
    tg_touch_user(
        $pdo,
        $chatId,
        $from,
        $state,
        is_array($stateRow['payload'] ?? null) ? $stateRow['payload'] : null
    );

    $hasConsent = tg_user_has_consent($pdo, $chatId);

    if ($text === '/start' || $text === '/menu' || $text === '/help') {
        if (!$hasConsent) {
            tg_log_action($pdo, $chatId, $from, 'start_consent_required', 'Запросил старт до принятия политики');
            tg_send_consent_prompt($chatId);
            tg_json_response(['ok' => true]);
        }

        tg_clear_user_state($pdo, $chatId, $from);
        tg_log_action($pdo, $chatId, $from, 'start', 'Открыл бота через /start');
        tg_send_main_menu($chatId);
        tg_json_response(['ok' => true]);
    }

    if (!$hasConsent) {
        tg_log_action($pdo, $chatId, $from, 'blocked_before_consent', 'Попытка использовать бота без принятия политики', [
            'text' => mb_substr($text, 0, 250, 'UTF-8'),
        ]);
        tg_send_consent_prompt($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($isText($text, ['Назад в меню', '↩️ Назад в меню'])) {
        tg_clear_user_state($pdo, $chatId, $from);
        tg_log_action($pdo, $chatId, $from, 'open_main_menu', 'Открыл главное меню');
        tg_send_main_menu($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($isText($text, ['Связаться с магазином', '📞 Связаться с магазином'])) {
        tg_clear_user_state($pdo, $chatId, $from);
        tg_log_action($pdo, $chatId, $from, 'contacts', 'Открыл контакты магазина');
        tg_send_contacts($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($isText($text, ['Написать сотрудникам магазина', '💬 Написать сотрудникам магазина'])) {
        tg_clear_user_state($pdo, $chatId, $from);
        tg_log_action($pdo, $chatId, $from, 'open_support_redirect', 'Открыл переход в бот техподдержки');
        tg_send_support_redirect($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($isText($text, ['Найти товар', '🔎 Найти товар'])) {
        tg_clear_user_state($pdo, $chatId, $from);
        tg_log_action($pdo, $chatId, $from, 'open_search_menu', 'Открыл меню поиска');
        tg_send_search_menu($chatId);
        tg_json_response(['ok' => true]);
    }

    if ($isText($text, ['По названию', '📝 По названию'])) {
        tg_set_user_state($pdo, $chatId, $from, 'wait_name');
        tg_log_action($pdo, $chatId, $from, 'choose_name_search', 'Выбрал поиск по названию');
        tg_send_message($chatId, 'Введите название товара одним сообщением.', [
            'reply_markup' => tg_reply_back_only_keyboard(),
        ]);
        tg_json_response(['ok' => true]);
    }

    if ($isText($text, ['По штрих-коду', '🔢 По штрих-коду'])) {
        tg_set_user_state($pdo, $chatId, $from, 'wait_barcode');
        tg_log_action($pdo, $chatId, $from, 'choose_barcode_search', 'Выбрал поиск по штрих-коду');
        tg_send_message($chatId, 'Введите штрих-код товара одним сообщением.', [
            'reply_markup' => tg_reply_back_only_keyboard(),
        ]);
        tg_json_response(['ok' => true]);
    }

    if ($isText($text, ['По артикулу', '🏷️ По артикулу'])) {
        tg_set_user_state($pdo, $chatId, $from, 'wait_article');
        tg_log_action($pdo, $chatId, $from, 'choose_article_search', 'Выбрал поиск по артикулу');
        tg_send_message($chatId, 'Введите артикул товара одним сообщением.', [
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
            tg_log_action($pdo, $chatId, $from, 'name_search_no_results', 'Поиск по названию без результатов', [
                'query' => $text,
            ]);
            tg_send_message($chatId, 'По указанному запросу товары не найдены. Уточните название и отправьте его ещё раз.', [
                'reply_markup' => tg_reply_back_only_keyboard(),
            ]);
            tg_json_response(['ok' => true]);
        }

        tg_clear_user_state($pdo, $chatId, $from);
        if (count($products) === 1) {
            tg_log_action($pdo, $chatId, $from, 'name_search_one_result', 'Поиск по названию: найден один товар', [
                'query' => $text,
                'product_id' => (int)$products[0]['id'],
                'product_name' => (string)($products[0]['name'] ?? ''),
            ]);
            tg_send_product_card($chatId, $products[0]);
        } else {
            tg_log_action($pdo, $chatId, $from, 'name_search_many_results', 'Поиск по названию: показан список товаров', [
                'query' => $text,
                'count' => count($products),
            ]);
            tg_send_product_choices($chatId, $products, $text);
        }
        tg_json_response(['ok' => true]);
    }

    if ($state === 'wait_barcode') {
        $product = tg_find_product_by_barcode($pdo, $text);
        if (!$product) {
            tg_log_action($pdo, $chatId, $from, 'barcode_search_no_results', 'Поиск по штрих-коду без результата', [
                'barcode' => $text,
            ]);
            tg_send_message($chatId, 'Товар с таким штрих-кодом не найден. Проверьте код и отправьте его ещё раз.', [
                'reply_markup' => tg_reply_back_only_keyboard(),
            ]);
            tg_json_response(['ok' => true]);
        }

        tg_clear_user_state($pdo, $chatId, $from);
        tg_log_action($pdo, $chatId, $from, 'barcode_search_result', 'Поиск по штрих-коду: открыт товар', [
            'barcode' => $text,
            'product_id' => (int)$product['id'],
            'product_name' => (string)($product['name'] ?? ''),
        ]);
        tg_send_product_card($chatId, $product);
        tg_json_response(['ok' => true]);
    }

    if ($state === 'wait_article') {
        $limit = max(10, (int)(tg_config()['search_results_limit'] ?? 10));
        $products = tg_search_products_by_article($pdo, $text, $limit);
        if (empty($products)) {
            tg_log_action($pdo, $chatId, $from, 'article_search_no_results', 'Поиск по артикулу без результата', [
                'article' => $text,
            ]);
            tg_send_message($chatId, 'Товар с таким артикулом не найден. Проверьте артикул и отправьте его ещё раз.', [
                'reply_markup' => tg_reply_back_only_keyboard(),
            ]);
            tg_json_response(['ok' => true]);
        }

        tg_clear_user_state($pdo, $chatId, $from);
        if (count($products) === 1) {
            tg_log_action($pdo, $chatId, $from, 'article_search_one_result', 'Поиск по артикулу: найден один товар', [
                'article' => $text,
                'product_id' => (int)$products[0]['id'],
                'product_name' => (string)($products[0]['name'] ?? ''),
            ]);
            tg_send_product_card($chatId, $products[0]);
        } else {
            tg_log_action($pdo, $chatId, $from, 'article_search_many_results', 'Поиск по артикулу: показан список товаров', [
                'article' => $text,
                'count' => count($products),
            ]);
            tg_send_product_choices($chatId, $products, $text);
        }
        tg_json_response(['ok' => true]);
    }

    tg_log_action($pdo, $chatId, $from, 'fallback_menu', 'Возврат в главное меню после произвольного сообщения', [
        'text' => mb_substr($text, 0, 250, 'UTF-8'),
    ]);
    tg_send_main_menu($chatId, 'Выберите действие с помощью кнопок ниже.');
    tg_json_response(['ok' => true]);
} catch (Throwable $e) {
    error_log('Telegram webhook error: ' . $e->getMessage());
    tg_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
