<?php

namespace App\Services\Payments;

use Shetabit\Multipay\Drivers\Zarinpal\Strategies\Normal;
use Shetabit\Multipay\Invoice;

/**
 * ZarinPal `normal` strategy with a bounded HTTP client (B-4).
 *
 * Identical to the vendor strategy except the constructor: the only
 * behavioral delta is the Guzzle client options. Tracks vendor ctor shape
 * (invoice + settings); PaymentTimeoutTest instantiates this class, so a
 * vendor upgrade that changes the parent constructor fails loudly in CI.
 */
class TimeoutZarinpalNormal extends Normal
{
    public function __construct(Invoice $invoice, array|object $settings)
    {
        $this->invoice($invoice);
        $this->settings = (object) $settings;
        $this->client = TimeoutHttpClient::make();
    }
}
