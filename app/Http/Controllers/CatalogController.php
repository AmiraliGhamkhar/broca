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
        $selectedSubject = $request->filled('subject')
            ? $subjects->firstWhere('slug', (string) $request->string('subject'))
            : null;

        $courses = Course::query()
            ->with(['subject', 'author', 'reviewer'])
            ->withCount([
                'videos as published_videos_count' => fn ($query) => $query->published(),
                'notes as published_notes_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()),
                'decks as published_decks_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()),
                'quizzes as published_quizzes_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()),
            ])
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

        return view('catalog.index', compact('courses', 'subjects', 'selectedSubject'));
    }

    public function subject(Subject $subject): View
    {
        abort_unless($subject->is_visible, 404);
        $courses = $subject->courses()
            ->with(['author', 'reviewer'])
            ->withCount([
                'videos as published_videos_count' => fn ($query) => $query->published(),
                'notes as published_notes_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()),
                'decks as published_decks_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()),
                'quizzes as published_quizzes_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()),
            ])
            ->published()
            ->orderBy('sort_order')
            ->paginate(12);

        return view('catalog.subject', compact('subject', 'courses'));
    }

    public function course(Course $course): View
    {
        abort_unless($course->status === 'published' && $course->published_at?->isPast(), 404);
        $course->load(['subject', 'author', 'reviewer']);

        $entitlements = app(\App\Services\EntitlementService::class);

        // The "رایگان" badge must reflect the EFFECTIVE entitlement (flag
        // AND global cap), not the bare flag — otherwise the page promises
        // free access the playback endpoint will 403 on.
        $videos = $course->videos()->published()->orderBy('sort_order')->get();
        $videos->each(fn ($video) => $video->is_free_available = $entitlements->isFree($video));
        $course->setRelation('videos', $videos);

        $notes = $course->notes()->where('status', 'published')->where('published_at', '<=', now())->orderBy('sort_order')->get();
        $notes->each(fn ($note) => $note->is_free_available = $entitlements->isFree($note));
        $course->setRelation('notes', $notes);

        $course->setRelation('decks', $course->decks()->where('status', 'published')->where('published_at', '<=', now())->orderBy('sort_order')->get());
        $course->setRelation('quizzes', $course->quizzes()->where('status', 'published')->where('published_at', '<=', now())->get());

        $isEnrolled = auth()->check() && auth()->user()->isEnrolledIn($course->id);

        return view('courses.show', compact('course', 'isEnrolled'));
    }
}
