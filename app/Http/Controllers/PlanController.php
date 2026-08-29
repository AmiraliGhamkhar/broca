<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        return view('plans', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'hasActiveSubscription' => $request->user()?->hasActiveSubscription() ?? false,
        ]);
    }
}
