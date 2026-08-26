<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Invoice;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'users' => User::count(),
            'courses' => Course::count(),
            'subjects' => Subject::count(),
            'activeSubscriptions' => Subscription::query()
                ->where('status', 'active')
                ->whereNotNull('activated_at')
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->count(),
            'paidInvoices' => Invoice::where('status', 'paid')->count(),
            'revenueIrr' => (int) Invoice::where('status', 'paid')->sum('amount_irr'),
            'videos' => \App\Models\Video::count(),
        ]);
    }
}
