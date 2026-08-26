<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Contributor;
use App\Models\Subject;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', ['courses' => Course::count(), 'contributors' => Contributor::count(), 'subjects' => Subject::count()]);
    }
}
