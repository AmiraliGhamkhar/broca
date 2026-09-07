<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\PlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Plan management for operators: prices, durations and activation — all
 * DB-driven, never hardcoded. Plans are locked product decisions (free /
 * 1-month / 3-month), so creation and deletion are intentionally not
 * exposed; ask an engineer to change the lineup.
 */
class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()->orderBy('sort_order')->get();

        return view('admin.plans.index', [
            'plans' => $plans,
            // The public /plans page can only show what is in this table, and
            // the deploy pipeline migrates without seeding — a missing row is
            // a MISSING CARD on the pricing page, which is how the lineup
            // "lost" two of its three cards. Say so, and name the fix.
            'missingCanonical' => array_values(array_diff(
                PlanCatalog::codes(),
                $plans->pluck('code')->filter()->all()
            )),
        ]);
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', ['plan' => $plan]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Integer Rial — the smallest unit; the view converts for display.
            'price_irr' => ['required', 'integer', 'min:0', 'max:2000000000'],
            'duration_months' => ['required', 'integer', 'min:0', 'max:36'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'price_irr.integer' => 'قیمت باید عدد صحیح (ریال) باشد.',
            'price_irr.min' => 'قیمت نمی‌تواند منفی باشد.',
            'duration_months.min' => 'مدت اشتراک نمی‌تواند منفی باشد.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $plan->update($validated);

        return redirect()->route('admin.plans.index')->with('success', 'پلان به‌روزرسانی شد.');
    }
}
