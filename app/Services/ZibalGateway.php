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
 * Zibal adapter over shetabit/payment (multipay), the secondary gateway
 * (client decision 2026-09-05: finish driver + callback). Mirrors
 * ZarinPalGateway so the two drivers stay behaviorally identical:
 * integer-Rial amounts (currency 'R' in config — multipay multiplies by 10
 * for 'T'), amount-bound server verification, receipt returned for the
 * payment ledger.
 *
 * Zibal's transaction id is the trackId (ZarinPal's is the Authority); the
 * callback sends `trackId` + `success` instead of `Authority` + `Status` —
 * the parameter mapping lives in PaymentController::zibalCallback.
 *
 * Selected via PAYMENT_GATEWAY=zibal (see AppServiceProvider's config-driven
 * binding). Per-user gateway choice at checkout remains a future product
 * decision (DECISIONS.md).
 */
class ZibalGateway implements PaymentGateway
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
                    // Zibal's trackId IS the transaction id — stored in the
                    // same authority column as ZarinPal's, so the finalizer,
                    // unique constraint and reconcile command stay
                    // gateway-agnostic.
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
            // Same 101-equivalent reading as the ZarinPal adapter: an already
            // verified trackId is a paid invoice, not a failure.
            $attached = property_exists($exception, 'receipt') ? $exception->receipt : null;

            // Receipt's constructor requires (driver, referenceId); the
            // authority column holds Zibal's trackId here — the same
            // transaction id the gateway knows.
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
        return 'zibal';
    }

    private function callbackUrl(): string
    {
        return (string) (config('payment.drivers.zibal.callbackUrl') ?: route('payments.zibal.callback'));
    }
}
