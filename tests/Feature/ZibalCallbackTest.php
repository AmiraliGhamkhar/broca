<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Subscription;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\Fixtures\FakePaymentGateway;
use Tests\TestCase;

/**
 * Zibal callback semantics (audit 2026-09-07 fix): the gateway posts
 * Status=1 (paid) or Status=2 (already verified at the gateway) — BOTH must
 * reach server-side, amount-bound verification; anything else must skip the
 * network call and fail the invoice closed.
 */
class ZibalCallbackTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_status_2_already_verified_reaches_server_verification_and_activates(): void
    {
        $invoice = Invoice::factory()->initiated()->create([
            'gateway' => 'zibal',
            'authority' => 'TRK-100',
        ]);

        $this->get(route('payments.zibal.callback', ['Status' => '2', 'trackId' => 'TRK-100']))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status, 'Status=2 must be treated as payable and re-verified server-side');
        $this->assertNotNull($invoice->paid_at);

        // The finalizer resolved the whole lifecycle, not just the invoice row.
        $this->assertDatabaseHas('subscriptions', [
            'invoice_id' => $invoice->id,
            'status' => 'active',
        ]);
    }

    public function test_status_1_paid_behaves_the_same(): void
    {
        $invoice = Invoice::factory()->initiated()->create([
            'gateway' => 'zibal',
            'authority' => 'TRK-200',
        ]);

        $this->get(route('payments.zibal.callback', ['Status' => '1', 'trackId' => 'TRK-200']))
            ->assertRedirect();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_unknown_status_skips_verification_and_fails_closed(): void
    {
        // The FakePaymentGateway would verify if asked — the invoice staying
        // unpaid proves the controller never called it for Status=0.
        $this->gateway->shouldVerify = true;

        $invoice = Invoice::factory()->initiated()->create([
            'gateway' => 'zibal',
            'authority' => 'TRK-300',
        ]);

        $this->get(route('payments.zibal.callback', ['Status' => '0', 'trackId' => 'TRK-300']))
            ->assertRedirect();

        $this->assertNotSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertDatabaseMissing('subscriptions', [
            'invoice_id' => $invoice->id,
        ]);
    }

    public function test_replay_of_an_already_paid_invoice_is_idempotent(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'gateway' => 'zibal',
            'authority' => 'TRK-400',
        ]);

        $this->get(route('payments.zibal.callback', ['Status' => '2', 'trackId' => 'TRK-400']))
            ->assertRedirect(route('checkout.success', $invoice));

        // The isPaid short-circuit fires BEFORE the ledger row is written —
        // a replayed callback must not add duplicate transactions.
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_unknown_track_id_redirects_safely_without_touching_anything(): void
    {
        $this->get(route('payments.zibal.callback', ['Status' => '1', 'trackId' => 'TRK-UNKNOWN']))
            ->assertRedirect(route('plans'));

        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertSame(0, Subscription::count());
    }
}
