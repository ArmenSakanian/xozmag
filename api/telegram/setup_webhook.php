<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

try {
    $baseUrl = tg_detect_base_url();
    $webhookUrl = $baseUrl . '/api/telegram/webhook.php';
    $secret = tg_webhook_secret();
    $dropPending = isset($_GET['drop']) && $_GET['drop'] === '1';

    $payload = [
        'url' => $webhookUrl,
        'allowed_updates' => ['message', 'callback_query'],
        'drop_pending_updates' => $dropPending,
    ];

    if ($secret !== '') {
        $payload['secret_token'] = $secret;
    }

    $setWebhook = tg_api_request('setWebhook', $payload);

    $setCommands = tg_api_request('setMyCommands', [
        'commands' => [
            ['command' => 'start', 'description' => 'Запустить бота'],
            ['command' => 'menu', 'description' => 'Главное меню'],
            ['command' => 'help', 'description' => 'Помощь'],
        ],
    ]);

    $webhookInfo = tg_api_request('getWebhookInfo');

    tg_json_response([
        'ok' => true,
        'message' => 'Webhook установлен.',
        'webhook_url' => $webhookUrl,
        'setWebhook' => $setWebhook,
        'setMyCommands' => $setCommands,
        'getWebhookInfo' => $webhookInfo,
    ]);
} catch (Throwable $e) {
    tg_json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
