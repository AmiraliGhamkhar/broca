<?php

namespace App\Services\Payments;

use Shetabit\Multipay\Invoice;

/**
 * Shared constructor for vendor drivers that need Broca's bounded HTTP client.
 */
trait InitializesTimeoutPaymentDriver
{
    public function __construct(Invoice $invoice, array|object $settings)
    {
        $this->invoice($invoice);
        $this->settings = (object) $settings;
        $this->client = TimeoutHttpClient::make();
    }
}
