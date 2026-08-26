<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Single resolution path for an invoice under the callback row-lock.
 *
 * Invariants:
 *  - A VERIFIED payment can flip pending/initiated/failed/expired/cancelled
 *    invoices to paid and activate the subscription exactly once. This is
 *    the heal path for "cancelled at the UI, completed at the gateway" —
 *    money captured must always result in access.
 *  - An UNVERIFIED callback can only fail pending/initiated invoices. A
 *    paid invoice can never be downgraded, and a verified payment can never
 *    be lost to a stale failure state.
 *  - Subscription creation is idempotent via the unique gateway_reference
 *    constraint (DB-level backstop).
 */
class PaymentFinalizer
{
    public function finalize(Invoice $invoice, bool $verified, ?PaymentTransaction $transaction = null): bool
    {
        return (bool) DB::transaction(function () use ($invoice, $verified, $transaction): bool {
            $lockQuery = Invoice::query()->whereKey($invoice->id);

            if ($verified) {
                // Heal set: every non-paid state is recoverable with proof
                // of payment from the gateway.
                $lockQuery->whereIn('status', [
                    Invoice::STATUS_PENDING,
                    Invoice::STATUS_INITIATED,
                    Invoice::STATUS_FAILED,
                    Invoice::STATUS_EXPIRED,
                    Invoice::STATUS_CANCELLED,
                ]);
            } else {
                $lockQuery->whereIn('status', [
                    Invoice::STATUS_PENDING,
                    Invoice::STATUS_INITIATED,
                ]);
            }

            $locked = $lockQuery->lockForUpdate()->first();

            if (! $locked) {
                // Another process resolved this invoice while we waited.
                return $invoice->fresh()?->isPaid() ?? false;
            }

            if (! $verified) {
                $transaction?->markFailed();
                $locked->markFailed();

                return false;
            }

            $transaction?->markVerified();
            $locked->markPaid();

            $this->ensureSubscription($locked);

            return true;
        });
    }

    /**
     * Create the subscription for a paid invoice if it is missing (drift
     * repair — also used by broca:reconcile-payments).
     */
    public function ensureSubscription(Invoice $invoice): ?Subscription
    {
        if ($invoice->subscription()->exists()) {
            return $invoice->subscription;
        }

        $plan = $invoice->plan;

        $subscription = Subscription::create([
            'user_id' => $invoice->user_id,
            'plan_id' => $invoice->plan_id,
            'invoice_id' => $invoice->id,
            'gateway' => $invoice->gateway,
            // Unique constraint = DB-level idempotency backstop.
            'gateway_reference' => $invoice->authority,
            'starts_at' => now(),
            'ends_at' => now()->addMonths(max(1, (int) $plan?->duration_months)),
        ]);
        $subscription->activate();

        return $subscription;
    }
}
