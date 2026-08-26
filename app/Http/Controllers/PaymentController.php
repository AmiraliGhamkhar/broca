<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Services\ZarinPalGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function checkout(Request $request, Plan $plan): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isActive() || ! $user->hasVerifiedEmail()) {
            abort(403);
        }

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'number' => 'INV-' . now()->format('Ymd') . '-' . Str::padLeft(random_int(1, 9999), 4, '0'),
            'amount_irr' => $plan->price_irr ?? $plan->id === 1 ? 0 : ($plan->id === 2 ? 50000 : 120000),
            'currency' => 'IRR',
        ]);

        try {
            $gateway = app(ZarinPalGateway::class);
            $paymentUrl = $gateway->startPayment($invoice);

            return redirect()->away($paymentUrl);
        } catch (\Throwable $e) {
            $invoice->markFailed();
            return redirect()->route('checkout.failed', $invoice)
                ->with('error', 'Failed to initiate payment. Please try again.');
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $authority = $request->query('Authority');
        $status = $request->query('Status');

        if (empty($authority) || $status !== 'OK') {
            return redirect()->route('checkout.failed', ['invoice' => null])
                ->with('error', 'Payment verification failed or cancelled.');
        }

        $gateway = app(ZarinPalGateway::class);
        $verified = $gateway->verifyPayment($authority);

        $invoice = Invoice::where('authority', $authority)->first();
        $paymentTransaction = PaymentTransaction::create([
            'invoice_id' => $invoice->id,
            'gateway' => $gateway->getGatewayName(),
            'request_payload' => $request->all(),
            'response_payload' => $request->query(),
            'reference_number' => $authority,
        ]);

        if ($verified && $invoice->isPending()) {
            $paymentTransaction->markVerified();
            $invoice->markPaid();

            $plan = $invoice->plan;
            $subscription = Subscription::create([
                'user_id' => $invoice->user_id,
                'plan_id' => $invoice->plan_id,
                'starts_at' => now(),
                'ends_at' => now()->addMonths($plan->duration_months ?? 1),
            ]);
            $subscription->activate();

            return redirect()->route('checkout.success', $invoice->id);
        }

        $invoice->markFailed();
        return redirect()->route('checkout.failed', $invoice->id);
    }

    public function success(Invoice $invoice): View
    {
        return view('payments.success', compact('invoice'));
    }

    public function failed(?Invoice $invoice = null): View
    {
        return view('payments.failed', compact('invoice'));
    }
}
