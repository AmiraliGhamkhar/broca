<?php

namespace App\Services\Payments;

use Shetabit\Multipay\Drivers\Zarinpal\Strategies\Zaringate;
use Shetabit\Multipay\Drivers\Zarinpal\Zarinpal;

/**
 * ZarinPal driver with timeout-bound strategies (B-4).
 *
 * The vendor driver resolves strategies through the public static
 * `$strategies` map, so overriding the map is the entire change — purchase,
 * pay and verify are inherited untouched. `zaringate` keeps the vendor class:
 * it speaks SOAP (no Guzzle client), the mode is unused, and PHP's
 * default_socket_timeout already bounds it.
 *
 * Wired in via config/payment.php `map` (the manager instantiates
 * `config['map'][$driver]`), not by edits to vendor/.
 */
class TimeoutZarinpal extends Zarinpal
{
    public static array $strategies = [
        'normal' => TimeoutZarinpalNormal::class,
        'sandbox' => TimeoutZarinpalSandbox::class,
        'zaringate' => Zaringate::class,
    ];
}
