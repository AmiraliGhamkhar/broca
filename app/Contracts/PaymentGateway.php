<?php

namespace App\Contracts;

use App\Models\Invoice;
use Shetabit\Multipay\Receipt;

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
     *
     * Returns the gateway's Receipt (reference id, amount, date) on success
     * so the caller can persist it in the payment ledger, or null when the
     * verification failed.
     */
    public function verifyPayment(Invoice $invoice): ?Receipt;

    public function getGatewayName(): string;
}
