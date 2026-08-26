<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === 'published' && $course->published_at?->isPast(), 404);

        $request->user()->enrollments()->firstOrCreate(
            ['course_id' => $course->id],
            ['enrolled_at' => now(), 'status' => 'active'],
        );

        return back()->with('status', 'ثبت‌نام در دوره انجام شد.');
    }
}
