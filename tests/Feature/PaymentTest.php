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

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_checkout_creates_invoice_with_the_plans_amount_and_redirects_to_gateway(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create(['price_irr' => 500000]);

        $response = $this->actingAs($user)->post(route('checkout', $plan));

        $this->assertStringStartsWith(
            '/fake-gateway?authority=FAKE-AUTH-',
            (string) $response->headers->get('Location'),
            'user is redirected to the gateway'
        );

        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount_irr' => 500000,
            'status' => 'initiated',
        ]);
    }

    public function test_checkout_reuses_a_live_invoice_instead_of_spamming_new_ones(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();

        $this->actingAs($user)->post(route('checkout', $plan));
        $this->actingAs($user)->post(route('checkout', $plan));

        $this->assertSame(1, Invoice::where('user_id', $user->id)->where('plan_id', $plan->id)->count());
    }

    public function test_checkout_refuses_the_free_plan_without_touching_the_gateway(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->free()->create();

        $response = $this->actingAs($user)->post(route('checkout', $plan));

        $response->assertRedirect(route('plans'));
        $this->assertDatabaseMissing('invoices', ['user_id' => $user->id]);
        $this->assertSame([], $this->gateway->startedInvoiceIds);
    }

    public function test_callback_success_verifies_marks_paid_and_activates_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create(['duration_months' => 1]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount_irr' => 500000,
        ]);
        $this->gateway->startPayment($invoice);

        $response = $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));

        $response->assertRedirect(route('checkout.success', $invoice));

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $invoice->id,
            'gateway' => 'fake',
            'reference_number' => $invoice->authority,
            'status' => 'verified',
        ]);

        $subscription = Subscription::query()->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($subscription, 'subscription linked to invoice');
        $this->assertSame('active', $subscription->status);
        $this->assertSame($invoice->authority, $subscription->gateway_reference);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    public function test_callback_is_idempotent_and_never_double_activates(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);
        $this->gateway->startPayment($invoice);

        $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));
        $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));

        $this->assertSame(1, Subscription::where('invoice_id', $invoice->id)->count());
        $this->assertSame(1, \App\Models\PaymentTransaction::where('invoice_id', $invoice->id)->count());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_callback_failure_marks_invoice_failed_without_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);
        $this->gateway->startPayment($invoice);

        $response = $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'NOK']));

        $response->assertRedirect(route('checkout.failed', $invoice));
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'failed']);
        $this->assertDatabaseMissing('subscriptions', ['invoice_id' => $invoice->id]);
    }

    public function test_callback_with_verification_failure_fails_closed(): void
    {
        $this->gateway->shouldVerify = false;

        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);
        $this->gateway->startPayment($invoice);

        $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'failed']);
        $this->assertDatabaseMissing('subscriptions', ['invoice_id' => $invoice->id]);
    }

    public function test_callback_with_unknown_authority_does_not_crash(): void
    {
        $response = $this->get(route('payments.zarinpal.callback', ['Authority' => 'UNKNOWN-AUTHORITY', 'Status' => 'OK']));

        $response->assertRedirect(route('plans'));
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_callback_replay_cannot_downgrade_a_paid_invoice(): void
    {
        $this->gateway->shouldVerify = false; // replay with failing verification

        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);
        $this->gateway->startPayment($invoice);

        // First: successful payment.
        $this->gateway->shouldVerify = true;
        $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);

        // Replay with the gateway refusing to re-verify: must stay paid.
        $this->gateway->shouldVerify = false;
        $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_payment_success_page_is_private_to_its_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->paid()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->get(route('checkout.success', $invoice))->assertForbidden();
        $this->actingAs($owner)->get(route('checkout.success', $invoice))->assertOk();
    }
}
