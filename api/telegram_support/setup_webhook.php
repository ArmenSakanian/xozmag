<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

try {
    $webhookUrl = tgs_detect_base_url() . '/api/telegram_support/webhook.php';
    $secret = tgs_webhook_secret();

    $payload = [
        'url' => $webhookUrl,
        'allowed_updates' => ['message'],
    ];
    if ($secret !== '') {
        $payload['secret_token'] = $secret;
    }

    $setWebhook = tgs_api_request('setWebhook', $payload);
    $setMyCommands = tgs_api_request('setMyCommands', [
        'commands' => [
            ['command' => 'start', 'description' => 'Запустить бота техподдержки'],
        ],
    ]);
    $webhookInfo = tgs_api_request('getWebhookInfo');

    tgs_json_response([
        'ok' => true,
        'message' => 'Webhook для бота техподдержки установлен.',
        'webhook_url' => $webhookUrl,
        'setWebhook' => $setWebhook,
        'setMyCommands' => $setMyCommands,
        'getWebhookInfo' => $webhookInfo,
    ]);
} catch (Throwable $e) {
    tgs_json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
