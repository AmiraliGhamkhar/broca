<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $subjects = Subject::query()->where('is_visible', true)->orderBy('sort_order')->get();

        $courses = Course::query()
            ->with(['subject', 'author', 'reviewer'])
            ->published()
            ->when(
                $request->filled('q'),
                // Escape LIKE wildcards so "%" / "_" in user input match literally.
                fn ($query) => $query->where('title', 'like', '%'.addcslashes((string) $request->string('q'), '\\%_').'%')
            )
            ->when(
                $request->filled('subject'),
                fn ($query) => $query->whereHas('subject', fn ($subject) => $subject->where('slug', (string) $request->string('subject')))
            )
            ->orderBy('sort_order')
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', compact('courses', 'subjects'));
    }

    public function subject(Subject $subject): View
    {
        abort_unless($subject->is_visible, 404);
        $courses = $subject->courses()->with(['author', 'reviewer'])->published()->orderBy('sort_order')->paginate(12);

        return view('catalog.subject', compact('subject', 'courses'));
    }

    public function course(Course $course): View
    {
        abort_unless($course->status === 'published' && $course->published_at?->isPast(), 404);
        $course->load(['subject', 'author', 'reviewer']);
        $course->setRelation('videos', $course->videos()->published()->orderBy('sort_order')->get());
        $course->setRelation('notes', $course->notes()->where('status', 'published')->where('published_at', '<=', now())->orderBy('sort_order')->get());
        $course->setRelation('decks', $course->decks()->where('status', 'published')->where('published_at', '<=', now())->orderBy('sort_order')->get());
        $course->setRelation('quizzes', $course->quizzes()->where('status', 'published')->where('published_at', '<=', now())->get());

        $isEnrolled = auth()->check() && auth()->user()->isEnrolledIn($course->id);

        return view('courses.show', compact('course', 'isEnrolled'));
    }
}
