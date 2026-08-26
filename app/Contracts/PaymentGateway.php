<?php

namespace App\Contracts;

use App\Models\Invoice;

interface PaymentGateway
{
    /**
     * Start payment process and return redirect URL
     */
    public function startPayment(Invoice $invoice): string;

    /**
     * Verify payment with gateway
     */
    public function verifyPayment(string $authority): bool;

    /**
     * Get gateway name for logging/transactions
     */
    public function getGatewayName(): string;
}