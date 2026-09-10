<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaymentFinalizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Shetabit\Multipay\Receipt;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentFinalizer $finalizer,
    ) {}

    /**
     * Create (or reuse) an invoice for the plan, then redirect to the gateway.
     */
    public function checkout(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless(config('broca.checkout_enabled'), 503, 'پرداخت تا زمان آماده‌سازی نهایی فعال نیست.');
        abort_unless($plan->is_active, 404);

        // The free tier needs no purchase — never send it through the gateway.
        if ((int) $plan->price_irr <= 0 || (int) $plan->duration_months < 1) {
            return redirect()->route('plans')->with('status', 'این پلان نیازی به خرید ندارد؛ دسترسی رایگان شما فعال است.');
        }

        $user = $request->user();

        // Never sell a second subscription while one is already active —
        // the user should renew after the current one ends.
        if ($user->hasActiveSubscription()) {
            return redirect()->route('plans')->with('status', 'هم‌اکنون اشتراک فعال دارید؛ پس از پایان اشتراک می‌توانید دوباره خرید کنید.');
        }

        // Reuse a live invoice for this plan+price (never send the user to
        // the gateway twice for two different invoices of the same intent).
        $invoice = Invoice::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('amount_irr', (int) $plan->price_irr)
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_INITIATED])
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('id')
            ->first();

        $invoice ??= $this->createInvoice($user, $plan);

        try {
            return redirect()->away($this->gateway->startPayment($invoice));
        } catch (\Throwable $exception) {
            report($exception);

            if (isset($invoice) && $invoice instanceof Invoice) {
                $invoice->markFailed();

                return redirect()->route('checkout.failed', $invoice)
                    ->with('error', 'شروع پرداخت ممکن نشد؛ لطفاً دوباره تلاش کنید.');
            }

            return redirect()->route('plans')
                ->with('error', 'ایجاد فاکتور ممکن نشد؛ لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Gateway callback: verify server-side, then activate the subscription
     * exactly once (idempotent under double callbacks and concurrent hits).
     */
    public function callback(Request $request): RedirectResponse
    {
        return $this->handleGatewayCallback($request, okStatus: 'OK');
    }

    /**
     * Zibal callback (secondary gateway). Zibal sends `trackId` + `success`
     * (1/2 = paid) instead of ZarinPal's `Authority` + `Status=OK`; after the
     * parameter mapping the flow is byte-for-byte the same money path.
     */
    public function zibalCallback(Request $request): RedirectResponse
    {
        return $this->handleGatewayCallback($request, okStatus: '1');
    }

    private function handleGatewayCallback(Request $request, string $okStatus): RedirectResponse
    {
        $authority = (string) ($request->query('Authority') ?? $request->query('trackId') ?? '');

        if ($authority === '') {
            return redirect()->route('plans')->with('error', 'پاسخ درگاه پرداخت نامعتبر بود.');
        }

        $invoice = Invoice::query()->where('authority', $authority)->first();

        if (! $invoice) {
            // Unknown authority — never crash; fail closed with a generic page.
            return redirect()->route('plans')->with('error', 'پرداخت یافت نشد یا نشست آن منقضی شده است.');
        }

        if ($invoice->isPaid()) {
            // Replay of an already-processed callback: idempotent success.
            return redirect()->route('checkout.success', $invoice);
        }

        // One transaction row per (gateway, authority) — replays reuse it.
        $transaction = $this->recordTransaction($invoice, $request);

        // Server-side verification, bound to the invoice's amount+authority.
        // A cancelled callback (Status !== OK / success !== 1) skips the
        // network call. The returned Receipt is persisted into the ledger
        // row before finalization so the forensic record carries the
        // gateway's reference id (Round-6 audit I-2).
        $receipt = null;

        if ((string) $request->query('Status', $request->query('success', '')) === $okStatus) {
            $receipt = $this->gateway->verifyPayment($invoice);
        }

        if ($receipt instanceof Receipt) {
            $this->attachReceipt($transaction, $receipt);
        }

        $activated = $this->finalizer->finalize($invoice, $receipt !== null, $transaction);

        return $activated
            ? redirect()->route('checkout.success', $invoice)
            : redirect()->route('checkout.failed', $invoice)->with('error', 'پرداخت لغو شد یا تأیید آن ناموفق بود؛ در صورت کسر مبلغ، تا ۷۲ ساعت بازگشت داده می‌شود.');
    }

    public function success(Request $request, Invoice $invoice): View
    {
        abort_unless($request->user() && $request->user()->id === (int) $invoice->user_id, 403);

        return view('payments.success', ['invoice' => $invoice, 'subscription' => $invoice->subscription]);
    }

    public function failed(Request $request, Invoice $invoice): View
    {
        abort_unless($request->user() && $request->user()->id === (int) $invoice->user_id, 403);

        return view('payments.failed', ['invoice' => $invoice]);
    }

    private function recordTransaction(Invoice $invoice, Request $request): PaymentTransaction
    {
        try {
            return PaymentTransaction::create([
                'invoice_id' => $invoice->id,
                'gateway' => $this->gateway->getGatewayName(),
                'request_payload' => $request->query(),
                'response_payload' => $request->query(),
                'reference_number' => $invoice->authority,
            ]);
        } catch (UniqueConstraintViolationException) {
            return PaymentTransaction::query()
                ->where('gateway', $this->gateway->getGatewayName())
                ->where('reference_number', $invoice->authority)
                ->firstOrFail();
        }
    }

    /**
     * Merge the gateway's verification receipt (driver, reference id, date)
     * into the transaction row so the ledger is self-sufficient for
     * chargeback disputes and accounting reconciliation (Round-6 audit I-2).
     *
     * Shetabit's Receipt exposes getters only (properties are protected
     * readonly, no toArray), so the array is built from the contract's
     * accessors explicitly.
     */
    private function attachReceipt(PaymentTransaction $transaction, Receipt $receipt): void
    {
        $payload = [
            'driver' => $receipt->getDriver(),
            'referenceId' => $receipt->getReferenceId(),
            'date' => $receipt->getDate()->toISOString(),
        ];

        $transaction->update([
            'response_payload' => array_merge((array) $transaction->response_payload, [
                'receipt' => $payload,
            ]),
        ]);
    }

    /**
     * Create the invoice, retrying on a unique-`number` collision (two
     * invoices in the same second drawing the same random suffix). The
     * probability is ~1/2.8e12 per second, but a collision must never
     * surface as a raw 500.
     */
    private function createInvoice(User $user, Plan $plan): Invoice
    {
        $attempts = 0;

        do {
            try {
                return Invoice::create([
                    'user_id' => $user->id,
                    'user_name_snapshot' => $user->name,
                    'user_email_snapshot' => $user->email,
                    'user_phone_snapshot' => $user->phone,
                    'plan_id' => $plan->id,
                    'number' => $this->nextInvoiceNumber(),
                    'amount_irr' => (int) $plan->price_irr,
                    'currency' => config('broca.currency', 'IRR'),
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if (++$attempts >= 5) {
                    throw $exception;
                }
            }
        } while (true);
    }

    private function nextInvoiceNumber(): string
    {
        // Timestamp (second precision) + 8 random chars: 36^8 ≈ 2.8e12
        // combinations per second — collisions are effectively impossible.
        return 'INV-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
    }
}
