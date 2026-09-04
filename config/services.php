<?php

return [
    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'sandbox' => filter_var(env('ZARINPAL_SANDBOX', true), FILTER_VALIDATE_BOOL),
        'base_url' => filter_var(env('ZARINPAL_SANDBOX', true), FILTER_VALIDATE_BOOL)
            ? 'https://sandbox.zarinpal.com/pg/v4'
            : 'https://api.zarinpal.com/pg/v4',
        'payment_url' => filter_var(env('ZARINPAL_SANDBOX', true), FILTER_VALIDATE_BOOL)
            ? 'https://sandbox.zarinpal.com/pg/StartPay'
            : 'https://www.zarinpal.com/pg/StartPay',
    ],

    'telegram' => [
        'enabled' => filter_var(env('TELEGRAM_BOT_ENABLED', false), FILTER_VALIDATE_BOOL),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'admin_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_ADMIN_IDS', ''))))),
        'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 30),
    ],
];
