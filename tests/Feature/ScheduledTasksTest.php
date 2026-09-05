<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The scheduler is the only thing running background work on cPanel shared
 * hosting (a single `schedule:run` cron; no supervisor, no daemon). If an
 * entry disappears, the failure is silent and only shows up as user-visible
 * breakage days later — so the critical entries are asserted here.
 */
class ScheduledTasksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function scheduledCommands(): array
    {
        return collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? $event->description ?? '')
            ->all();
    }

    private function assertScheduled(string $needle): void
    {
        $commands = $this->scheduledCommands();

        $this->assertTrue(
            collect($commands)->contains(fn (string $c) => str_contains($c, $needle)),
            "Expected a scheduled task containing [{$needle}]. Scheduled: ".json_encode($commands, JSON_UNESCAPED_SLASHES)
        );
    }

    public function test_queue_is_drained_by_the_scheduler(): void
    {
        // Verification + password-reset mail implement ShouldQueue on the
        // database queue. Without this entry those emails are never sent and
        // the `verified` middleware locks every new user out of the
        // dashboard and checkout.
        $this->assertScheduled('queue:work');
    }

    public function test_queue_worker_is_cron_safe_and_not_a_daemon(): void
    {
        $worker = collect($this->scheduledCommands())
            ->first(fn (string $c) => str_contains($c, 'queue:work'));

        $this->assertNotNull($worker);

        // Shared hosting forbids long-running daemons: the worker must exit.
        $this->assertStringContainsString('--stop-when-empty', $worker);
        // Bounded runtime so an every-minute tick cannot pile up workers.
        $this->assertStringContainsString('--max-time', $worker);
        // Poison messages must not be retried forever.
        $this->assertStringContainsString('--tries', $worker);
    }

    public function test_financial_safety_nets_are_scheduled(): void
    {
        // Heals invoices paid at the gateway but unresolved locally. Without
        // it, captured money can fail to grant access.
        $this->assertScheduled('broca:reconcile-payments');

        // Without this, subscriptions never expire and paid access is
        // effectively permanent.
        $this->assertScheduled('broca:expire-subscriptions');
    }

    public function test_session_and_cache_tables_are_pruned(): void
    {
        // Round-6 audit D-1: SESSION_DRIVER=database keeps a row (with IP +
        // user agent) per visitor forever unless pruned — a capped shared
        // disk and an unbounded PII retention window in one.
        $this->assertScheduled('session:prune');

        // Round-6 audit D-2: the database cache store never deletes expired
        // rows; rate-limiter keys alone grow it by a row per user per window.
        $this->assertScheduled('broca:prune-cache');
    }
}
