<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Exceptions\PreviouslyVerifiedException;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;
use Shetabit\Multipay\Exceptions\TimeoutException;
use Shetabit\Multipay\Invoice as MultipayInvoice;
use Shetabit\Multipay\Payment;
use Shetabit\Multipay\Receipt;

/**
 * ZarinPal adapter over shetabit/payment (multipay). Amounts are integers in
 * Rial (smallest unit) — the driver config sets currency to 'R' so the
 * package does NOT multiply by 10.
 *
 * Zibal can be added the same way later: the driver already ships in the
 * package, only a callback route + param mapping are needed.
 */
class ZarinPalGateway implements PaymentGateway
{
    public function __construct(private readonly Payment $payment) {}

    public function startPayment(Invoice $invoice): string
    {
        $multipayInvoice = (new MultipayInvoice)
            ->amount((int) $invoice->amount_irr)
            ->detail('description', 'خرید اشتراک بروکا — فاکتور '.$invoice->number);

        $this->payment
            ->callbackUrl($this->callbackUrl())
            ->purchase(
                $multipayInvoice,
                function ($driver, $transactionId) use ($invoice): void {
                    // The ZarinPal authority IS the transaction id.
                    $invoice->initiate($this->getGatewayName(), (string) $transactionId);
                }
            );

        return $this->payment->pay()->getActionUrl();
    }

    public function verifyPayment(Invoice $invoice): ?Receipt
    {
        if (! $invoice->authority) {
            return null;
        }

        try {
            return $this->payment
                ->amount((int) $invoice->amount_irr)
                ->transactionId($invoice->authority)
                ->verify();
        } catch (PreviouslyVerifiedException $exception) {
            // The gateway already verified this authority once — treat as paid;
            // invoice-level locking keeps activation idempotent. The driver
            // signals 101 ("previously verified") by throwing, so recover the
            // attached receipt when the package version provides one and fall
            // back to a minimal driver-tagged receipt otherwise.
            $attached = property_exists($exception, 'receipt') ? $exception->receipt : null;

            // Receipt's constructor requires (driver, referenceId); in this
            // recovery path the best available reference is the invoice's
            // stored authority (ZarinPal's Authority string).
            return $attached instanceof Receipt
                ? $attached
                : new Receipt($this->getGatewayName(), (string) $invoice->authority);
        } catch (InvalidPaymentException|PurchaseFailedException|TimeoutException) {
            return null;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function getGatewayName(): string
    {
        return 'zarinpal';
    }

    private function callbackUrl(): string
    {
        return (string) (config('payment.drivers.zarinpal.callbackUrl') ?: route('payments.zarinpal.callback'));
    }
}
