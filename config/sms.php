<?php

/*
|--------------------------------------------------------------------------
| SMS (outbound text messages)
|--------------------------------------------------------------------------
|
| Broca sends two kinds of text message today: the signup verification code
| (a one-time passcode bound to the account's mobile number) and operator
| test messages sent from `php artisan broca:sms:test`.
|
| DELIVERY IS DRIVER-BASED AND FAIL-SOFT BY DESIGN.
|
| Iranian SMS panels all speak different dialects (Kavenegar / Melipayamak /
| sms.ir / a reseller's own gateway), and the client had not picked one when
| this path was built. Rather than hard-code a vendor and ship a credential
| we cannot test, the default driver is `log`: messages are written to the
| application log and nothing leaves the server. Going live is a .env change
| (SMS_DRIVER=http + SMS_HTTP_URL/body template), not a code change.
|
| Two invariants the rest of the app depends on:
|  1. `SmsManager::send()` never throws for a *disabled* or *unconfigured*
|     driver — a missing credential must not break registration.
|  2. A transport failure throws `SmsDeliveryException`, and the callers that
|     sit in a user-facing request (the notification channel) catch it. A
|     dead SMS vendor therefore costs the user a resend button, never a 500.
|
*/

return [

    // Master switch. false = every send is a no-op (logged, not delivered).
    'enabled' => (bool) env('SMS_ENABLED', true),

    // log | null | http
    'default' => (string) env('SMS_DRIVER', 'log'),

    // Sender number / header shown on the handset. Most Iranian panels
    // require a pre-approved number, so an unset value is passed as null and
    // the template's `:from` placeholder renders as an empty string.
    'from' => env('SMS_FROM'),

    'drivers' => [

        /*
         * Development/CI driver. Writes one log line per message and reports
         * success, so the whole OTP flow can be exercised end to end without
         * spending credit or leaking a real handset number into a vendor log.
         */
        'log' => [
            'channel' => env('SMS_LOG_CHANNEL', 'stack'),
        ],

        /*
         * Explicit discard. Used by CI and by hosts that must never dial out
         * (a staging clone of production data, for instance).
         */
        'null' => [],

        /*
         * Generic HTTP panel driver — the operator's escape hatch.
         *
         * The request is fully described by the environment: a URL, a method,
         * a JSON body template and optional extra headers. Every scalar value
         * in the body (and the URL) is run through placeholder substitution,
         * which is what makes one driver cover vendors whose field names are
         * `receptor`/`message`, `to`/`text`, or anything else.
         *
         * Placeholders: :to (E.164-ish local number, digits only), :message,
         * :from (sender number), :code (the OTP, when the message carries
         * one), :reference (a per-send id, handy for correlating panel logs).
         */
        'http' => [
            'url' => env('SMS_HTTP_URL'),
            'method' => strtoupper((string) env('SMS_HTTP_METHOD', 'POST')),
            // json | form — how the body template is encoded.
            'encode' => strtolower((string) env('SMS_HTTP_ENCODE', 'json')),
            // Extra headers as a JSON object, e.g. {"X-API-KEY":"${SMS_API_KEY}"}
            'headers' => json_decode((string) (env('SMS_HTTP_HEADERS') ?: '[]'), true) ?: [],
            // Body template as a JSON object: {"receptor":":to","message":":message"}
            'body' => json_decode((string) (env('SMS_HTTP_BODY') ?: '{"recipient":":to","message":":message"}'), true) ?: [],
            // A send counts as delivered when the response status is in this
            // list AND — when set — the body contains this string. Panels
            // happily answer HTTP 200 with a JSON error body.
            'success_status' => array_values(array_filter(array_map(
                'intval',
                explode(',', (string) (env('SMS_HTTP_SUCCESS_STATUS') ?: '200,201,202'))
            ))),
            'success_contains' => env('SMS_HTTP_SUCCESS_CONTAINS'),
            'timeout' => (int) env('SMS_HTTP_TIMEOUT', 15),
            // Only turn this off for a panel with a broken certificate chain;
            // it is a deliberate, loud decision, not a default.
            'verify' => (bool) env('SMS_HTTP_VERIFY', true),
        ],

    ],

];
