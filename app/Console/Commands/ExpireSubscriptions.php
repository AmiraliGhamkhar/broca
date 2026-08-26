<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Scheduled hourly in routes/console.php.
     */
    protected $signature = 'broca:expire-subscriptions';

    protected $description = 'Mark expired subscriptions and stale gateway invoices';

    public function handle(): int
    {
        $expired = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => 'expired']);

        // Invoices initiated at the gateway whose authority window has
        // elapsed without a callback are expired so they are never reused.
        $staleInvoices = Invoice::query()
            ->where('status', Invoice::STATUS_INITIATED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => Invoice::STATUS_EXPIRED]);

        $this->info("Expired subscriptions: {$expired}, stale invoices: {$staleInvoices}");

        return self::SUCCESS;
    }
}
