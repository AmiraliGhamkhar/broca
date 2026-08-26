<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $enrollments = request()->user()->enrollments()->with('course.subject')->latest('enrolled_at')->get();

        return view('learner.dashboard', compact('enrollments'));
    }
}
