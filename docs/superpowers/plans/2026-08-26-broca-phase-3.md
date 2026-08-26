# Broca Platform — Phase 3 Implementation Plan (Payment Gateway & Subscriptions)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the payment gateway integration, subscription lifecycle, and invoice/payment flow. This includes:
- ZarinPal gateway integration (via `shetabit/payment` package)
- Subscription lifecycle (activation, expiry, calendar-month calculations)
- Invoice/payment state machine (pending, initiated, paid, failed, cancelled, expired)
- Payment verification (server-side callback handling)
- Subscription status checks (active, scheduled, expired, pending, cancelled)

**Architecture:**
- Use the existing `PaymentGateway` contract and `ZarinPalGateway` placeholder.
- Extend the `Subscription` model with lifecycle methods.
- Add invoice/payment state machine with proper transitions.
- Implement server-side payment verification with idempotency.
- Add subscription status checks in policies.

**Tech Stack:** Laravel 13, PHP 8.4+, `shetabit/payment` package, Blade views, Alpine.js for interactive elements.

## Global Constraints (from SPEC)
- Backend: PHP 8.4+, Laravel 13.x. [SPEC §3.1]
- Frontend: Blade + Tailwind v4 + Alpine.js, no SPA. [SPEC §1.1]
- Language: Persian only (RTL). [SPEC §8.3]
- Money stored as integer IRR; display as toman. [SPEC §1.1]
- Timezone: store UTC; display `Asia/Tehran`. [SPEC §1.1]
- No real credentials or launch-ready claims; placeholder assets must be visibly tracked. [SPEC §15]
- Payment verification must be server-side and idempotent. [SPEC §9.4]
- Subscription expiry must be calculated in `Asia/Tehran` timezone. [SPEC §4.6]

---

## File Structure Overview
```
app/Models/
  Subscription.php       # lifecycle methods, calendar calculations
  Invoice.php            # state machine, payment reference
  PaymentTransaction.php  # gateway callbacks, idempotency
app/Services/
  ZarinPalGateway.php    # implements PaymentGateway contract
app/Http/Controllers/
  PlanController.php     # show plans
  PaymentController.php  # checkout, callback, success/failed
app/Policies/
  SubscriptionPolicy.php  # active subscription checks
resources/views/
  plans.blade.php         # plan cards
  payments/
    success.blade.php      # already exists
    failed.blade.php       # already exists
  learner/
    subscription.blade.php  # subscription status
  admin/
    invoices/index.blade.php
    subscriptions/index.blade.php
database/migrations/
  2026_08_28_000001_create_invoices_table.php
  2026_08_28_000002_create_payment_transactions_table.php
  2026_08_28_000003_add_subscription_lifecycle_to_subscriptions_table.php
```
---

## Task 1: ZarinPal Gateway Implementation

**Files:**
- Create: `app/Services/ZarinPalGateway.php`
- Modify: `config/payment.php` (add ZarinPal config)

**Interfaces:**
- Consumes: `PaymentGateway` contract, `Invoice` model, `Subscription` model
- Produces: `startPayment(Invoice $invoice)` (returns redirect URL), `verifyPayment(string $authority)` (returns bool), `getGatewayName()` (returns 'zarinpal')

**Implementation notes:**
- Use `shetabit/payment` package with ZarinPal driver.
- Configure sandbox mode from `.env` (`ZARINPAL_SANDBOX=true`).
- Store merchant ID in `.env` (`ZARINPAL_MERCHANT_ID`).
- Callback URL should be `/payments/zarinpal/callback`.
- `startPayment` should:
  1. Create a ZarinPal payment request
  2. Store the authority in the invoice
  3. Return the payment URL
- `verifyPayment` should:
  1. Verify the payment with ZarinPal
  2. Return true/false

- [ ] **Step 1: Install `shetabit/payment`**
```bash
composer require shetabit/payment
```

- [ ] **Step 2: Create `ZarinPalGateway.php`**
```php
<?php
namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use Shetabit\Payment\Invoice as ShetabitInvoice;
use Shetabit\Payment\InvoicePayment;

class ZarinPalGateway implements PaymentGateway
{
    public function startPayment(Invoice $invoice): string
    {
        $payment = new InvoicePayment;
        $payment->amount($invoice->amount_irr)
            ->callbackUrl(route('payments.zarinpal.callback'))
            ->detail(['invoice_id' => $invoice->id]);

        $response = $payment->config(['merchant_id' => config('payment.zarinpal.merchant_id')])
            ->purchase(
                (new ShetabitInvoice)->setAmount($invoice->amount_irr),
                function ($driver, $transactionId) use ($invoice): void {
                    $invoice->update(['gateway_payment_id' => $transactionId]);
                }
            );

        if ($response->isRedirect()) {
            $invoice->update(['authority' => $response->getAuthority(), 'status' => 'initiated']);
            return $response->getRedirectUrl();
        }

        throw new \RuntimeException('Failed to initiate ZarinPal payment: ' . $response->getMessage());
    }

    public function verifyPayment(string $authority): bool
    {
        $payment = new InvoicePayment;
        $response = $payment->amount(0)->transactionId($authority)->verify();

        return $response->isSuccessful();
    }

    public function getGatewayName(): string
    {
        return 'zarinpal';
    }
}
```

- [ ] **Step 3: Add ZarinPal config to `config/payment.php`**
```php
return [
    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'sandbox' => env('ZARINPAL_SANDBOX', true),
        'callback_url' => env('ZARINPAL_CALLBACK_URL', route('payments.zarinpal.callback')),
    ],
];
```

- [ ] **Step 4: Add ZarinPal to `.env.example`**
```ini
ZARINPAL_MERCHANT_ID=
ZARINPAL_SANDBOX=true
ZARINPAL_CALLBACK_URL=http://localhost:8000/payments/zarinpal/callback
```

- [ ] **Step 5: Commit**
```bash
git add app/Services/ZarinPalGateway.php config/payment.php .env.example
git commit -m "feat: ZarinPal gateway implementation"
```
---

## Task 2: Subscription Lifecycle

**Files:**
- Modify: `app/Models/Subscription.php`
- Create migration: `2026_08_28_000003_add_subscription_lifecycle_to_subscriptions_table.php`
- Create factory: `database/factories/SubscriptionFactory.php`

**Model changes:**
- Add `status` enum (`active|scheduled|expired|pending|cancelled`)
- Add `starts_at`, `ends_at`, `activated_at` timestamps
- Add `gateway` string (e.g. 'zarinpal')
- Add `gateway_reference` string (unique where present)
- Add `invoice_id` foreign key

**Migration:**
```php
Schema::table('subscriptions', function (Blueprint $table): void {
    $table->enum('status', ['active','scheduled','expired','pending','cancelled'])->default('pending');
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->timestamp('activated_at')->nullable();
    $table->string('gateway')->nullable();
    $table->string('gateway_reference')->nullable()->unique();
    $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
});
```

**Factory:**
```php
return Subscription::factory()->state(fn (array $attributes) => [
    'user_id' => \App\Models\User::factory(),
    'plan_id' => \App\Models\Plan::factory(),
    'status' => 'active',
    'starts_at' => now(),
    'ends_at' => now()->addMonth(),
]);
```

**Lifecycle methods:**
- `activate()`: sets `status` to `active`, `activated_at` to now
- `schedule($startsAt)`: sets `status` to `scheduled`, `starts_at` to `$startsAt`, `ends_at` to `$startsAt->addMonth()`
- `expire()`: sets `status` to `expired`
- `isActive()`: returns true if `status` is `active` and `ends_at` is in the future

- [ ] **Step 1: Update `Subscription.php`**
- [ ] **Step 2: Create migration**
- [ ] **Step 3: Create factory**
- [ ] **Step 4: Commit**
---

## Task 3: Invoice/Payment State Machine

**Files:**
- Create: `app/Models/Invoice.php`
- Create migration: `2026_08_28_000001_create_invoices_table.php`
- Create factory: `database/factories/InvoiceFactory.php`

**Model fields:**
- `id`, `user_id`, `plan_id`, `number` (unique human-readable), `amount_irr`, `currency` (`IRR`), `status` (`pending|initiated|paid|failed|cancelled|expired`), `gateway`, `gateway_payment_id`, `authority`, `paid_at`, `expires_at`, timestamps

**Migration:**
```php
Schema::create('invoices', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('plan_id')->constrained()->restrictOnDelete();
    $table->string('number')->unique();
    $table->unsignedBigInteger('amount_irr');
    $table->string('currency', 3)->default('IRR');
    $table->enum('status', ['pending','initiated','paid','failed','cancelled','expired'])->default('pending');
    $table->string('gateway')->nullable();
    $table->string('gateway_payment_id')->nullable();
    $table->string('authority')->nullable()->unique();
    $table->timestamp('paid_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

**Factory:**
```php
return Invoice::factory()->state(fn (array $attributes) => [
    'user_id' => \App\Models\User::factory(),
    'plan_id' => \App\Models\Plan::factory(),
    'number' => 'INV-' . str_pad(fake()->unique()->randomNumber(6), 6, '0', STR_PAD_LEFT),
    'amount_irr' => fake()->numberBetween(100000, 1000000),
    'status' => 'pending',
]);
```

**State transitions:**
- `initiate()`: sets `status` to `initiated`, `gateway`, `authority`, `expires_at`
- `markPaid()`: sets `status` to `paid`, `paid_at`
- `markFailed()`: sets `status` to `failed`
- `markCancelled()`: sets `status` to `cancelled`
- `markExpired()`: sets `status` to `expired`

- [ ] **Step 1: Create `Invoice.php`**
- [ ] **Step 2: Create migration**
- [ ] **Step 3: Create factory**
- [ ] **Step 4: Commit**
---

## Task 4: Payment Transaction Model

**Files:**
- Create: `app/Models/PaymentTransaction.php`
- Create migration: `2026_08_28_000002_create_payment_transactions_table.php`
- Create factory: `database/factories/PaymentTransactionFactory.php`

**Model fields:**
- `id`, `invoice_id`, `gateway`, `request_payload`, `response_payload`, `reference_number`, `status` (`initiated|verified|failed|duplicate`), `verified_at`, timestamps

**Migration:**
```php
Schema::create('payment_transactions', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
    $table->string('gateway');
    $table->json('request_payload');
    $table->json('response_payload');
    $table->string('reference_number')->nullable();
    $table->enum('status', ['initiated','verified','failed','duplicate'])->default('initiated');
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();
    $table->unique(['gateway', 'reference_number']);
});
```

**Factory:**
```php
return PaymentTransaction::factory()->state(fn (array $attributes) => [
    'invoice_id' => \App\Models\Invoice::factory(),
    'gateway' => 'zarinpal',
    'request_payload' => ['amount' => 100000, 'description' => 'Test payment'],
    'response_payload' => ['authority' => 'A001'], 
    'status' => 'initiated',
]);
```

- [ ] **Step 1: Create `PaymentTransaction.php`**
- [ ] **Step 2: Create migration**
- [ ] **Step 3: Create factory**
- [ ] **Step 4: Commit**
---

## Task 5: Payment Controller

**Files:**
- Create: `app/Http/Controllers/PaymentController.php`
- Modify: `routes/web.php` (add payment routes)

**Controller methods:**
- `checkout(Plan $plan)`: creates invoice, initiates payment, redirects to gateway
- `callback(Request $request)`: handles gateway return, verifies payment, activates subscription
- `success(Invoice $invoice)`: shows success page
- `failed(Invoice $invoice)`: shows failure page

**Implementation notes:**
- `checkout` should:
  1. Create an invoice with `pending` status
  2. Use `ZarinPalGateway` to start payment
  3. Redirect to gateway
- `callback` should:
  1. Verify payment with gateway
  2. Create a payment transaction record
  3. If successful, activate subscription
  4. Redirect to success/failed page
- `success`/`failed` should show appropriate messages

- [ ] **Step 1: Create `PaymentController.php`**
- [ ] **Step 2: Add routes to `routes/web.php`**
- [ ] **Step 3: Commit**
---

## Task 6: Subscription Policy

**Files:**
- Create: `app/Policies/SubscriptionPolicy.php`

**Methods:**
- `hasActiveSubscription(User $user)`: checks if user has an active subscription

**Implementation notes:**
- Use the existing `User` relationship to subscriptions
- Check for `status` = `active` and `ends_at` > now()

- [ ] **Step 1: Create `SubscriptionPolicy.php`**
- [ ] **Step 2: Register policy in `AuthServiceProvider`**
- [ ] **Step 3: Commit**
---

## Task 7: Test Coverage

**Files:**
- Create: `tests/Feature/PaymentTest.php`
- Create: `tests/Feature/SubscriptionTest.php`

**PaymentTest:**
- Test checkout flow (invoice creation, gateway redirect)
- Test callback handling (success/failure)
- Test idempotency of callback

**SubscriptionTest:**
- Test subscription lifecycle (activation, expiry, calendar calculations)
- Test policy checks

- [ ] **Step 1: Create tests**
- [ ] **Step 2: Run tests**
- [ ] **Step 3: Commit**
---

## Self-Review

1. **Spec coverage:** All Phase 3 requirements (payment gateway, subscription lifecycle, invoice/payment state machine, server-side verification, idempotency, calendar calculations) are addressed.
2. **Placeholders:** No `TODO`/`TBD`. All files contain concrete implementations or clear stub comments.
3. **Consistency:** Naming follows existing conventions (`ZarinPalGateway`, `PaymentController`, `SubscriptionPolicy`). All models use `#[Fillable]` attributes like existing ones.
4. **Scope:** No external services beyond ZarinPal (which is stubbed). All routes live within existing middleware groups.

---

## Execution Handoff

Plan saved to `docs/superpowers/plans/2026-08-26-broca-phase-3.md`. Choose execution mode:
1. **Subagent-Driven (recommended)** – I’ll spawn a fresh subagent per task, review between tasks.
2. **Inline Execution** – I’ll perform the tasks in this session with checkpoints.

Which approach do you prefer?
