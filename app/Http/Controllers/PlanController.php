<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Support\PlanCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    /**
     * Canonical lineup (client-confirmed 2026-09-07): three cards —
     * رایگان / اشتراک یک‌ماهه ۲۷۰ تومان / اشتراک سه‌ماهه ۶۰۰ تومان.
     * Rows come from `plans` (price_irr is stored in Rial; 2 700 = 270
     * Toman) and database/seeders/DatabaseSeeder.php is the source of truth.
     */
    public function index(Request $request): View
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        // The free tier is a product constant (EntitlementService caps), not
        // something an admin can toggle off in the plans table. If no
        // zero-price row exists (fresh install, DB trimmed), surface the
        // canonical free tier so the page never hides it.
        //
        // The two PAID tiers are not invented here: they are database content
        // that `php artisan broca:sync-plans` (wired into the deploy hook)
        // writes from App\Support\PlanCatalog. A host whose plans table was
        // never populated shows this one card and nothing else — which reads
        // as "the other two cards disappeared" — so the admin panel and the
        // Telegram bot both surface that gap instead of leaving it silent.
        if (! $plans->contains(fn (Plan $plan) => (int) $plan->price_irr === 0)) {
            $plans->prepend(new Plan(PlanCatalog::free()));
        }

        return view('plans', [
            'plans' => $plans,
            'hasActiveSubscription' => $request->user()?->hasActiveSubscription() ?? false,
        ]);
    }
}
