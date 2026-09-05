<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        // The free tier is a product constant (EntitlementService caps), not
        // something an admin can toggle off in the plans table. If no
        // zero-price row exists (fresh install, DB trimmed), surface the
        // canonical free tier so the page never hides it.
        if (! $plans->contains(fn (Plan $plan) => (int) $plan->price_irr === 0)) {
            $plans->prepend(new Plan([
                'code' => 'free',
                'name' => 'پلن پایه رایگان',
                'description' => 'تا ۲ ویدیوی منتخب، ۱ جزوه، ۱۰ فلش‌کارت و ۱ سؤال آزمون در کل آرشیو — برای ارزیابی پیش از خرید.',
                'duration_months' => 0,
                'price_irr' => 0,
                'sort_order' => 0,
                'is_active' => true,
            ]));
        }

        return view('plans', [
            'plans' => $plans,
            'hasActiveSubscription' => $request->user()?->hasActiveSubscription() ?? false,
        ]);
    }
}
