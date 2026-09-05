<?php

// NOTE: there is deliberately NO zarinpal block here (Round-6 audit B-1).
// Gateway credentials live exclusively in config/payment.php (the shetabit/
// multipay driver config) — a second, partially divergent credentials block
// in services.php misled operators and disagreed with the live driver URLs.

return [
    'telegram' => [
        'enabled' => filter_var(env('TELEGRAM_BOT_ENABLED', false), FILTER_VALIDATE_BOOL),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'admin_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_ADMIN_IDS', ''))))),
        'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 30),
    ],
];
