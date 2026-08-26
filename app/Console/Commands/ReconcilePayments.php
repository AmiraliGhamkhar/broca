<?php

namespace App\Console\Commands;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Services\PaymentFinalizer;
use Illuminate\Console\Command;

/**
 * Financial safety net, scheduled daily: re-verifies failed/expired/cancelled
 * invoices against the gateway and heals any case where money was captured
 * but the invoice never resolved to paid ("cancelled at the UI, completed at
 * the gateway", late callbacks, replay races). Also repairs paid invoices
 * whose subscription row is missing.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'broca:reconcile-payments {--days=7 : Look back N days}';

    protected $description = 'Heal invoices paid at the gateway but not resolved locally';

    public function handle(PaymentGateway $gateway, PaymentFinalizer $finalizer): int
    {
        $since = now()->subDays((int) $this->option('days'));

        $candidates = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_FAILED, Invoice::STATUS_EXPIRED, Invoice::STATUS_CANCELLED])
            ->whereNotNull('authority')
            ->whereNotNull('gateway')
            ->where('updated_at', '>=', $since)
            ->orderBy('id')
            ->get();

        $healed = 0;
        $stillFailed = 0;

        foreach ($candidates as $invoice) {
            try {
                if ($gateway->verifyPayment($invoice)) {
                    $finalizer->finalize($invoice, verified: true);
                    $healed++;
                    $this->info("Healed invoice {$invoice->number} ({$invoice->status} → paid).");
                } else {
                    $stillFailed++;
                }
            } catch (\Throwable $exception) {
                report($exception);
                $this->error("Verification error on invoice {$invoice->number}: {$exception->getMessage()}");
            }
        }

        // Drift repair: paid invoices without a subscription row.
        $orphans = Invoice::query()
            ->where('status', Invoice::STATUS_PAID)
            ->whereDoesntHave('subscription')
            ->where('updated_at', '>=', $since)
            ->get();

        foreach ($orphans as $orphan) {
            try {
                $finalizer->ensureSubscription($orphan);
                $this->info("Recreated missing subscription for invoice {$orphan->number}.");
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $this->info("Reconciled: {$healed} healed, {$stillFailed} confirmed failed, {$orphans->count()} subscriptions repaired.");

        if ($healed > 0) {
            // Healed invoices mean money was captured without access —
            // surface it loudly so support proactively contacts the users.
            logger()->channel('single')->warning('broca:reconcile-payments healed '.$healed.' invoice(s).');
        }

        return self::SUCCESS;
    }
}
