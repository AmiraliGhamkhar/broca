<?php

namespace Tests\Fixtures;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use Shetabit\Multipay\Receipt;

class FakePaymentGateway implements PaymentGateway
{
    public bool $shouldVerify = true;

    /** @var array<int, int> */
    public array $startedInvoiceIds = [];

    public function startPayment(Invoice $invoice): string
    {
        $this->startedInvoiceIds[] = $invoice->id;

        $invoice->initiate($this->getGatewayName(), 'FAKE-AUTH-'.$invoice->id);

        return '/fake-gateway?authority=FAKE-AUTH-'.$invoice->id;
    }

    public function verifyPayment(Invoice $invoice): ?Receipt
    {
        if (! $this->shouldVerify) {
            return null;
        }

        // Receipt's constructor requires (driver, referenceId); the
        // reference is readonly — pass it at construction.
        return new Receipt($this->getGatewayName(), 'FAKE-REF-'.$invoice->id);
    }

    public function getGatewayName(): string
    {
        return 'fake';
    }
}
