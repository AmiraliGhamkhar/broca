<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Support\PlanCatalog;
use Illuminate\Console\Command;

/**
 * Reconcile the `plans` table with App\Support\PlanCatalog.
 *
 * IDEMPOTENT BY CONSTRUCTION: it is run by the cPanel deploy hook on every
 * release, so it must be safe to run a hundred times.
 *
 *   broca:sync-plans          create whatever is missing; never touch a price
 *                             an operator has edited
 *   broca:sync-plans --reset  also restore canonical names/prices/durations
 *                             and re-activate the canonical three
 *
 * WHY IT EXISTS: the deploy pipeline migrates but never seeds, so a host whose
 * `plans` table was never populated by hand shows ONE card on /plans (the
 * controller's built-in free fallback) instead of the three-card lineup the
 * client signed off. This command makes the lineup a property of the code
 * again, without taking price editing away from the operator.
 */
class SyncPlans extends Command
{
    protected $signature = 'broca:sync-plans
                            {--reset : Restore canonical names, prices, durations and ordering too}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Ensure the canonical plan lineup (free / 1-month / 3-month) exists in the plans table';

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        $dryRun = (bool) $this->option('dry-run');

        $created = 0;
        $updated = 0;

        foreach (PlanCatalog::lineup() as $definition) {
            $plan = Plan::query()->where('code', $definition['code'])->first();

            if (! $plan) {
                $created++;

                if (! $dryRun) {
                    Plan::query()->create($definition);
                }

                $this->line(sprintf(
                    '%s <info>%s</info> (%s) — %s تومان / %s ماه',
                    $dryRun ? '[dry-run] would create' : 'created',
                    $definition['name'],
                    $definition['code'],
                    number_format(intdiv($definition['price_irr'], 10)),
                    $definition['duration_months']
                ));

                continue;
            }

            if (! $reset) {
                continue;
            }

            $changes = array_diff_assoc(
                array_map(static fn ($value) => (string) $value, $definition),
                array_map(static fn ($value) => (string) $value, $plan->only(array_keys($definition)))
            );

            if ($changes === []) {
                continue;
            }

            $updated++;

            if (! $dryRun) {
                $plan->update($definition);
            }

            $this->line(sprintf(
                '%s <info>%s</info>: %s',
                $dryRun ? '[dry-run] would reset' : 'reset',
                $definition['code'],
                implode(', ', array_keys($changes))
            ));
        }

        // Sort order must not collide: two plans sharing a sort_order make the
        // order of the cards on /plans depend on the storage engine.
        if (! $dryRun) {
            $this->dedupeSortOrder();
        }

        $this->newLine();
        $this->line(sprintf(
            'Lineup: %d plan(s) in the table — created %d, reset %d%s.',
            Plan::query()->count(),
            $created,
            $updated,
            $dryRun ? ' (dry run, nothing written)' : ''
        ));

        return self::SUCCESS;
    }

    /**
     * Give any plan left on a duplicate sort_order the next free slot, so the
     * three canonical cards keep a deterministic left-to-right order.
     */
    private function dedupeSortOrder(): void
    {
        $taken = [];

        foreach (Plan::query()->orderBy('sort_order')->orderBy('id')->get() as $plan) {
            $order = (int) $plan->sort_order;

            if (isset($taken[$order])) {
                $order = $taken === [] ? 0 : max(array_keys($taken)) + 1;
                $plan->forceFill(['sort_order' => $order])->saveQuietly();
            }

            $taken[$order] = true;
        }
    }
}
