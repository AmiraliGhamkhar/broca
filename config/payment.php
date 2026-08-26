<?php

/**
 * shetabit/payment (multipay) configuration. The package's own default config
 * (driver map, API URLs for 30+ gateways) is merged with this file at runtime,
 * so only credentials and behavior need to be set here.
 *
 * Money is stored and sent in RIAL (smallest unit) → currency 'R'. The
 * package multiplies amounts by 10 when currency is 'T' (Toman) — do not
 * change this unless the whole app switches units.
 */
return [
    'default' => env('PAYMENT_GATEWAY', 'zarinpal'),

    'drivers' => [
        'zarinpal' => [
            // normal | sandbox | zaringate
            'mode' => env('ZARINPAL_SANDBOX', false) ? 'sandbox' : 'normal',
            'merchantId' => env('ZARINPAL_MERCHANT_ID'),
            'callbackUrl' => env('ZARINPAL_CALLBACK_URL'),
            'description' => 'خرید اشتراک بروکا',
            'currency' => 'R',
        ],
        'zibal' => [
            // normal | direct
            'mode' => 'normal',
            'merchantId' => env('ZIBAL_MERCHANT_ID'),
            'callbackUrl' => env('ZIBAL_CALLBACK_URL'),
            'description' => 'خرید اشتراک بروکا',
            'currency' => 'R',
        ],
    ],
];
