<?php

namespace App\Services\Payments;

use Shetabit\Multipay\Drivers\Zibal\Zibal;

/**
 * Zibal driver with a bounded HTTP client (B-4).
 *
 * Zibal has no strategy layer — the driver builds its own Guzzle client in
 * the constructor — so overriding the constructor is the entire change.
 * Wired in via config/payment.php `map`.
 */
class TimeoutZibal extends Zibal
{
    use InitializesTimeoutPaymentDriver;
}
