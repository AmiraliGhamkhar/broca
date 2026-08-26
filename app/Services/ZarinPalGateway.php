<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use Shetabit\Payment\Invoice as ShetabitInvoice;
use Shetabit\Payment\InvoicePayment;

class ZarinPalGateway implements PaymentGateway
{
    public function startPayment(Invoice $invoice): string
    {
        $payment = new InvoicePayment;
        $payment->amount($invoice->amount_irr)
            ->callbackUrl(route('payments.zarinpal.callback'))
            ->detail(['invoice_id' => $invoice->id]);

        $response = $payment->config(['merchant_id' => config('payment.zarinpal.merchant_id')])
            ->purchase(
                (new ShetabitInvoice)->setAmount($invoice->amount_irr),
                function ($driver, $transactionId) use ($invoice): void {
                    $invoice->update(['gateway_payment_id' => $transactionId]);
                }
            );

        if ($response->isRedirect()) {
            $invoice->update([
                'authority' => $response->getAuthority(),
                'status' => 'initiated',
            ]);
            return $response->getRedirectUrl();
        }

        throw new \RuntimeException('Failed to initiate ZarinPal payment: ' . $response->getMessage());
    }

    public function verifyPayment(string $authority): bool
    {
        $payment = new InvoicePayment;
        $response = $payment->amount(0)->transactionId($authority)->verify();

        return $response->isSuccessful();
    }

    public function getGatewayName(): string
    {
        return 'zarinpal';
    }
}
