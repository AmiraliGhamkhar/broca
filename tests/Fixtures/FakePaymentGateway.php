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

        $receipt = new Receipt($this->getGatewayName());
        $receipt->referenceId = 'FAKE-REF-'.$invoice->id;

        return $receipt;
    }

    public function getGatewayName(): string
    {
        return 'fake';
    }
}
