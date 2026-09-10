<?php

namespace App\Services\Payments;

use GuzzleHttp\Client;

/**
 * The one place gateway HTTP timeouts are defined.
 *
 * shetabit/multipay (v3.0.4, verified in vendor) builds every strategy with
 * a bare `new Client()` — Guzzle's default timeout is 0, i.e. a hung gateway
 * holds a PHP worker until max_execution_time. On shared hosting a few of
 * those during a slow-gateway episode can stall the whole account's worker
 * pool (Round-6 audit B-4, verified Round 10 now that vendor/ is readable).
 *
 * 15 s total / 5 s connect: far above ZarinPal/Zibal p99, far below any
 * worker limit. A timeout surfaces as an exception the app gateways already
 * translate (verify → null, purchase → failed page with Persian copy).
 */
final class TimeoutHttpClient
{
    public const TIMEOUT_SECONDS = 15;

    public const CONNECT_TIMEOUT_SECONDS = 5;

    public static function make(): Client
    {
        return new Client([
            'timeout' => self::TIMEOUT_SECONDS,
            'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
        ]);
    }
}
