# Audit — Payments (ZarinPal / Zibal)

Date: 2026-09-05 · Scope: `PaymentController`, `PaymentFinalizer`,
`ZarinPalGateway`, `Invoice`, `Subscription`, `config/payment.php`,
`broca:reconcile-payments`.

**Verdict: the money path is the strongest part of this codebase.** The core
invariants a payment integration must hold are all present and correct. Findings
below are gaps at the edges, not structural defects. One (no checkout throttle)
is fixed in this pass; two require your decision before going live.

---

## What is already correct — verified, not assumed

These are worth stating explicitly because they are the things that usually go
wrong, and here they don't:

1. **Verification is server-side and amount-bound.**
   `ZarinPalGateway::verifyPayment()` sends `->amount((int) $invoice->amount_irr)`
   with the authority. A tampered callback cannot mark a 5,000,000-Rial invoice
   paid for 1,000 Rial — ZarinPal rejects the mismatch. The browser's `Status=OK`
   is never trusted on its own (`PaymentController::callback`).

2. **Currency unit is unambiguous.** `config/payment.php` sets `'currency' => 'R'`
   with a comment explaining that multipay multiplies by 10 for `'T'`. Amounts are
   integer Rial end to end. This is the single most common Iranian-gateway bug
   (10× under/overcharge) and it is handled deliberately.

3. **Idempotency is enforced at three layers**, which is the right design:
   - application: `$invoice->isPaid()` short-circuits replays;
   - row lock: `PaymentFinalizer` re-reads under `lockForUpdate()` inside a
     transaction, so two concurrent callbacks serialise;
   - database: `subscriptions.gateway_reference` is `UNIQUE`, and
     `payment_transactions` has `UNIQUE(gateway, reference_number)`.
   Even if the first two were bypassed, the DB refuses a double subscription.

4. **The "cancelled at UI, captured at gateway" case is handled.**
   `PaymentFinalizer`'s heal set lets a *verified* payment promote an invoice out
   of `failed`/`expired`/`cancelled`, while an *unverified* callback can only
   fail `pending`/`initiated`. A paid invoice can never be downgraded. This is
   exactly the asymmetry you want: money captured must always end in access.

5. **One resolution path, shared with reconciliation.** `broca:reconcile-payments`
   calls the same `PaymentFinalizer`, so the safety net cannot drift from the
   live path. Genuinely good architecture.

6. **Invoice numbers survive collision.** `createInvoice()` retries up to 5×
   on a unique violation rather than surfacing a 500.

7. **Receipt pages are ownership-checked** (`success`/`failed` both
   `abort_unless($request->user()->id === $invoice->user_id, 403)`), so invoice
   IDs are not enumerable across users.

---

## Findings

### P1 — `/checkout` had no rate limit · FIXED

**Finding.** `routes/web.php:122` registered `POST /checkout/{plan}` with no
throttle, while login, registration, password reset, video progress and admin
2FA all have one. Every call performs an outbound ZarinPal purchase request and
may insert an invoice row.

**Impact.** A double-clicking user or a trivial script sprays gateway requests
and invoice rows. ZarinPal rate-limits merchants, so sustained abuse risks the
merchant account's standing — a shared-hosting DB is the second victim, not the
first.

**Fix.** `->middleware('throttle:checkout')`, limiter defined in
`AppServiceProvider`: 6/minute **keyed by user id, not IP**.

**Rationale.** IP keying is wrong for this product: Iranian universities and
hospitals NAT large numbers of students behind one address, so an IP key would
let one user's retries lock out an entire faculty. User keying is safe here
because the route is already behind `auth` + `verified`.

### P2 — Zibal is half-wired · NEEDS YOUR DECISION

**Finding.** `config/payment.php` defines a full Zibal driver block, `.env.example`
carries `ZIBAL_MERCHANT_ID` / `ZIBAL_CALLBACK_URL`, but there is **no Zibal
callback route** and `ZarinPalGateway` is bound unconditionally in
`AppServiceProvider::register()`. `PaymentGateway::getGatewayName()` returns the
hardcoded string `'zarinpal'`.

**Impact.** Setting `PAYMENT_GATEWAY=zibal` in `.env` today would send users to
Zibal and then have **no route able to verify the return** — payments captured,
access never granted. The config invites a misconfiguration that loses money.

**Options.**
- **A (recommended for launch): remove the Zibal block** from `config/payment.php`
  and `.env.example`. Half-built money paths are a liability; re-add it properly
  when it is actually wanted.
- **B: finish it** — add `/payments/zibal/callback`, make the container binding
  switch on `config('payment.default')`, and derive `getGatewayName()` from the
  active driver rather than a literal.

I have **not** changed this: removing a payment option is a business decision.
Tell me A or B.

### P3 — Callback has no signature/IP verification · ACCEPTED RISK (documented)

**Finding.** `/payments/zarinpal/callback` is an unauthenticated GET keyed only
by `Authority`.

**Assessment.** This is **acceptable and correct** for ZarinPal, because the
callback is only a *trigger* — the authoritative decision comes from the
server-to-server `verify()` call bound to the amount. Forging a callback with a
random authority yields "invoice not found"; forging with a real authority still
requires ZarinPal to confirm payment. No change needed. Documented so a future
reviewer doesn't "fix" it into something worse.

### P4 — `expires_at` is 30 minutes, gateway sessions can outlive it

**Finding.** `Invoice::initiate()` sets `expires_at = now()+30min`. ZarinPal
sessions can be completed slightly later, and `broca:expire-subscriptions` also
expires stale gateway invoices.

**Assessment.** Not a money-loss risk — the finalizer's heal set explicitly
promotes `expired` invoices on a verified payment, so a late completion still
grants access. Worth knowing when reading logs; no code change recommended.

### P5 — `report($exception)` on gateway failure needs log hygiene at launch

**Finding.** `ZarinPalGateway::verifyPayment()` catches `\Throwable` and calls
`report()`. With `LOG_LEVEL=debug` (the `.env.example` default) and
`APP_DEBUG=true`, gateway exception payloads land in `storage/logs`.

**Fix (deployment-time, not code).** Production `.env` must set
`APP_DEBUG=false` and `LOG_LEVEL=error`. Already covered in the launch
checklist below; flagged here because merchant IDs can appear in driver
exception context.

---

## Pre-launch checklist for going live with real money

- [ ] `ZARINPAL_SANDBOX=false` and a real `ZARINPAL_MERCHANT_ID`.
- [ ] `ZARINPAL_CALLBACK_URL=https://brocamed.ir/payments/zarinpal/callback`
      and the identical URL registered in the ZarinPal panel.
- [ ] `BROCA_CHECKOUT_ENABLED=true` — currently `false`, so checkout 503s.
- [ ] **Scheduler cron installed** (see AUDIT-02). Without it
      `broca:reconcile-payments` never runs: that is the net that catches
      payments captured at ZarinPal but unresolved locally.
- [ ] Real plan prices set — `price_irr` still holds placeholder values.
- [ ] `APP_DEBUG=false`, `LOG_LEVEL=error`.
- [ ] Decide P2 (Zibal: remove or finish).
- [ ] One real end-to-end purchase of the cheapest plan, then verify:
      invoice `paid`, subscription `active`, and a **second** hit of the same
      callback URL still lands on the success page without creating a second
      subscription.
