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
];
