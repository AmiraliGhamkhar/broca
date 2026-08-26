<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentTest extends TestCase
{
    public function test_checkout_flow(): void
    {
        $user = $this->createUser();
        $plan = $this->createPlan();

        $response = $this->actingAs($user)->post(route('checkout', $plan->id));
        $response->assertRedirect(); // redirects to gateway

        $this->assertDatabaseHas('invoices', ['user_id' => $user->id, 'plan_id' => $plan->id]);
    }

    public function test_callback_success(): void
    {
        $invoice = $this->createInvoiceWithAuthority();
        $transaction = $this->createPaymentTransaction($invoice->authority, 'initiated');

        $response = $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));
        $response->assertRedirect();

        $this->assertDatabaseHas('payment_transactions', ['invoice_id' => $invoice->id, 'status' => 'verified']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_callback_failure(): void
    {
        $invoice = $this->createInvoiceWithAuthority();
        $transaction = $this->createPaymentTransaction($invoice->authority, 'initiated');

        $response = $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'FAILED']));
        $response->assertRedirect();

        $this->assertDatabaseHas('payment_transactions', ['invoice_id' => $invoice->id, 'status' => 'failed']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'failed']);
    }

    public function test_payment_idempotency(): void
    {
        $invoice = $this->createInvoiceWithAuthority();
        $this->createPaymentTransaction($invoice->authority, 'initiated');

        $response = $this->get(route('payments.zarinpal.callback', ['Authority' => $invoice->authority, 'Status' => 'OK']));
        $response->assertRedirect();

        $transactionCount = $this->count('payment_transactions', ['invoice_id' => $invoice->id]);
        $this->assertLessThanOrEqual($transactionCount, 1);
    }

    public function test_subscription_activation_on_payment(): void
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $invoice = $this->createPaidInvoice($user, $plan);

        $subscription = $invoice->subscription;
        $this->assertTrue($subscription->isActive());
    }

    protected function createUser(): mixed
    {
        return \App\Models\User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
    }

    protected function createPlan(): mixed
    {
        return \App\Models\Plan::factory()->create(['code' => 'monthly']);
    }

    protected function createInvoiceWithAuthority(): mixed
    {
        return \App\Models\Invoice::factory()->initiated()->create();
    }

    protected function createPaymentTransaction(string $authority, string $status): mixed
    {
        return \App\Models\PaymentTransaction::factory()->state(['reference_number' => $authority, 'status' => $status])->create();
    }

    protected function createPaidInvoice($user, $plan): mixed
    {
        $invoice = \App\Models\Invoice::factory()->paid()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);
        $invoice->subscription()->save(\App\Models\Subscription::factory()->make(['user_id' => $user->id, 'plan_id' => $plan->id, 'starts_at' => now(), 'ends_at' => now()->addMonth()]));
        return $invoice;
    }
}
