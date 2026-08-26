<?php

namespace App\Contracts;

use App\Models\Invoice;

interface PaymentGateway
{
    /**
     * Start a payment for the invoice at the gateway and return the URL the
     * user's browser must be redirected to. Implementations must persist the
     * gateway authority/transaction id on the invoice (Invoice::initiate).
     */
    public function startPayment(Invoice $invoice): string;

    /**
     * Verify the payment server-side. Must bind the verification to the
     * invoice's amount and authority — never trust the client redirect alone.
     */
    public function verifyPayment(Invoice $invoice): bool;

    public function getGatewayName(): string;
}
