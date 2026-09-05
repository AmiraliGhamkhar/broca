<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_invoice_number_collision_retries_with_a_fresh_number_instead_of_500ing(): void
    {
        $this->travelTo('2026-08-29 10:00:00');

        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create(['price_irr' => 500000]);

        // Force the very next generated number to collide with an existing
        // invoice, then let the retry draw a different suffix. The request
        // also draws 40-char strings internally (session id, CSRF token), so
        // the factory branches on length instead of using a positional
        // sequence that those draws would eat first.
        $stamp = 'INV-'.now()->format('YmdHis').'-';
        Invoice::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'number' => $stamp.'AAAAAAAA',
            'amount_irr' => 999999, // different price → never reused
        ]);
        $suffixDraws = 0;
        Str::createRandomStringsUsing(function (int $length) use (&$suffixDraws): string {
            if ($length !== 8) {
                // Unrelated framework draw (session id / CSRF): real entropy
                // without recursing into the Str factory.
                return substr(strtr(base64_encode(random_bytes($length * 2)), '+/', '-_'), 0, $length);
            }

            $suffixDraws++;

            return $suffixDraws === 1 ? 'AAAAAAAA' : 'BBBBBBBB';
        });

        try {
            $response = $this->actingAs($user)->post(route('checkout', $plan));
        } finally {
            Str::createRandomStringsNormally();
        }

        $this->assertStringStartsWith(
            '/fake-gateway?authority=FAKE-AUTH-',
            (string) $response->headers->get('Location'),
            'checkout must survive the collision and reach the gateway'
        );
        $this->assertDatabaseHas('invoices', ['user_id' => $user->id, 'number' => $stamp.'BBBBBBBB']);
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

    public function test_checkout_refuses_when_user_already_has_an_active_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create(['price_irr' => 500000]);

        Subscription::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('checkout', $plan));

        $response->assertRedirect(route('plans'));
        $this->assertDatabaseMissing('invoices', ['user_id' => $user->id]);
        $this->assertSame([], $this->gateway->startedInvoiceIds);
    }

    public function test_checkout_refuses_while_a_lifetime_subscription_null_ends_at_is_active(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create(['price_irr' => 500000]);

        // NULL ends_at means "active forever" (User::isActive contract).
        Subscription::factory()->create(['user_id' => $user->id, 'ends_at' => null]);

        $response = $this->actingAs($user)->post(route('checkout', $plan));

        $response->assertRedirect(route('plans'));
        $this->assertDatabaseMissing('invoices', ['user_id' => $user->id]);
        $this->assertSame([], $this->gateway->startedInvoiceIds);
    }

    public function test_checkout_is_allowed_again_after_the_subscription_expired(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create(['price_irr' => 500000]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'activated_at' => now()->subMonths(2),
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($user)->post(route('checkout', $plan));

        $this->assertStringStartsWith(
            '/fake-gateway?authority=FAKE-AUTH-',
            (string) $response->headers->get('Location'),
            'expired subscription no longer blocks a fresh purchase'
        );
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

    public function test_gateway_verification_receipt_is_persisted_in_the_ledger(): void
    {
        // Round-6 audit I-2: the verify receipt (gateway reference id) is the
        // field a chargeback dispute or accounting reconciliation needs — it
        // must land in payment_transactions.response_payload, not evaporate.
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);
        $this->gateway->startPayment($invoice);

        $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));

        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $invoice->id,
            'gateway' => 'fake',
            'reference_number' => $invoice->authority,
            'status' => 'verified',
        ]);

        $payload = PaymentTransaction::query()
            ->where('invoice_id', $invoice->id)
            ->where('reference_number', $invoice->authority)
            ->first()
            ?->response_payload;

        $this->assertIsArray($payload);
        $this->assertSame('FAKE-REF-'.$invoice->id, $payload['receipt']['referenceId'] ?? null);
    }
}
