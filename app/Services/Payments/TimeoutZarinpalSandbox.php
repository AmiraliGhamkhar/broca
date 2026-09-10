<?php

namespace App\Services\Payments;

use Shetabit\Multipay\Drivers\Zarinpal\Strategies\Sandbox;
use Shetabit\Multipay\Invoice;

/**
 * ZarinPal `sandbox` strategy with a bounded HTTP client (B-4).
 *
 * Same rationale as TimeoutZarinpalNormal; the sandbox endpoint hangs the
 * same way a production one does, and pre-launch testing is when a stuck
 * worker is most confusing.
 */
class TimeoutZarinpalSandbox extends Sandbox
{
    public function __construct(Invoice $invoice, array|object $settings)
    {
        $this->invoice($invoice);
        $this->settings = (object) $settings;
        $this->client = TimeoutHttpClient::make();
    }
}
