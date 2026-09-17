<?php

namespace App\Services\Payments;

use Shetabit\Multipay\Drivers\Zarinpal\Strategies\Sandbox;

/**
 * ZarinPal `sandbox` strategy with a bounded HTTP client (B-4).
 *
 * Same rationale as TimeoutZarinpalNormal; the sandbox endpoint hangs the
 * same way a production one does, and pre-launch testing is when a stuck
 * worker is most confusing.
 */
class TimeoutZarinpalSandbox extends Sandbox
{
    use InitializesTimeoutPaymentDriver;
}
