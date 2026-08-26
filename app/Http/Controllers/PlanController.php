<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('plans', ['plans' => Plan::where('is_active', true)->orderBy('sort_order')->get()]);
    }
}
