<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\FakePaymentGateway;
use Tests\TestCase;

/**
 * The scheduled financial jobs (routes/console.php) are the only thing that
 * ever expires subscriptions and heals gateway-confirmed payments — they
 * must keep working.
 */
class PaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_subscriptions_command_expires_only_past_ends_at(): void
    {
        $user = User::factory()->create();
        $expired = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'starts_at' => now()->subMonths(2),
            'activated_at' => now()->subMonths(2),
            'ends_at' => now()->subMinute(),
        ]);
        $live = Subscription::factory()->create(['user_id' => $user->id]);

        $this->artisan('broca:expire-subscriptions')->assertSuccessful();

        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('active', $live->fresh()->status);
    }

    public function test_expire_subscriptions_command_expires_stale_gateway_invoices(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();

        $stale = Invoice::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Invoice::STATUS_INITIATED,
            'authority' => 'STALE-1',
            'gateway' => 'fake',
            'expires_at' => now()->subMinute(),
        ]);
        $stillLive = Invoice::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Invoice::STATUS_INITIATED,
            'authority' => 'LIVE-1',
            'gateway' => 'fake',
            'expires_at' => now()->addHour(),
        ]);

        $this->artisan('broca:expire-subscriptions')->assertSuccessful();

        $this->assertSame(Invoice::STATUS_EXPIRED, $stale->fresh()->status);
        $this->assertSame(Invoice::STATUS_INITIATED, $stillLive->fresh()->status);
    }

    public function test_reconcile_heals_invoices_paid_at_the_gateway(): void
    {
        $gateway = new FakePaymentGateway;
        $gateway->shouldVerify = true;
        $this->app->instance(PaymentGateway::class, $gateway);

        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Invoice::STATUS_FAILED,
            'authority' => 'HEAL-1',
            'gateway' => 'fake',
            'updated_at' => now()->subDay(),
        ]);

        $this->artisan('broca:reconcile-payments')->assertSuccessful();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertTrue($user->fresh()->hasActiveSubscription());
        $this->assertDatabaseHas('subscriptions', ['invoice_id' => $invoice->id]);
    }

    public function test_reconcile_leaves_unverified_invoices_failed(): void
    {
        $gateway = new FakePaymentGateway;
        $gateway->shouldVerify = false;
        $this->app->instance(PaymentGateway::class, $gateway);

        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Invoice::STATUS_FAILED,
            'authority' => 'NOPE-1',
            'gateway' => 'fake',
        ]);

        $this->artisan('broca:reconcile-payments')->assertSuccessful();

        $this->assertSame(Invoice::STATUS_FAILED, $invoice->fresh()->status);
        $this->assertFalse($user->fresh()->hasActiveSubscription());
    }

    public function test_reconcile_repairs_missing_subscriptions_on_paid_invoices(): void
    {
        $gateway = new FakePaymentGateway;
        $gateway->shouldVerify = true;
        $this->app->instance(PaymentGateway::class, $gateway);

        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();
        $invoice = Invoice::factory()->paid()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'authority' => 'ORPHAN-1',
            'gateway' => 'fake',
        ]);

        $this->assertFalse($user->hasActiveSubscription());

        $this->artisan('broca:reconcile-payments')->assertSuccessful();

        $this->assertTrue($user->fresh()->hasActiveSubscription());
        $this->assertDatabaseHas('subscriptions', ['invoice_id' => $invoice->id]);
    }

    public function test_backup_command_refuses_non_mysql_drivers(): void
    {
        // The suite runs on sqlite; the command must refuse loudly instead
        // of attempting a root mysqldump (audit finding: no credential
        // fallback and no silent wrong-driver runs).
        $this->artisan('broca:backup-database')
            ->assertExitCode(\Symfony\Component\Console\Command\Command::INVALID);
    }
}
