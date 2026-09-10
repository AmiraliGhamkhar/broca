<?php

use App\Services\Payments\TimeoutZarinpal;
use App\Services\Payments\TimeoutZibal;

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

    // The manager instantiates config['map'][$driver]. These subclasses are
    // the vendor drivers with bounded HTTP clients (Round-6 audit B-4,
    // verified + fixed Round 10): shetabit/multipay builds a bare
    // `new Client()` (no timeout), so a hung gateway would hold a PHP worker
    // until max_execution_time. mergeConfigFrom() is a shallow merge, so this
    // key replaces the package's 30-driver map — reachability is unchanged
    // because the `drivers` key below already constrains the app to these two.
    'map' => [
        'zarinpal' => TimeoutZarinpal::class,
        'zibal' => TimeoutZibal::class,
    ],

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
