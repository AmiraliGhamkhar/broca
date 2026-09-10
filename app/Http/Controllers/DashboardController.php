<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FlashcardReview;
use App\Models\UserFlashcardSchedule;
use App\Services\EntitlementService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();

        // Active Enrollments with rich course relations
        $enrollments = $user->enrollments()
            ->with([
                'course.subject',
                'course.author',
                'course.reviewer',
                'course.videos' => fn ($q) => $q->published()->orderBy('sort_order'),
                'course.notes' => fn ($q) => $q->published()->orderBy('sort_order'),
                // Published-only count matches the learner flashcards hub;
                // counting drafts here over-promised what study pages serve (D-8).
                'course.decks' => fn ($q) => $q->published()->withCount(['cards' => fn ($cq) => $cq->published()]),
                'course.quizzes' => fn ($q) => $q->published(),
            ])
            ->latest('enrolled_at')
            ->get();

        // Effective free status (flag AND global cap) for the badges —
        // same rule the playback/download endpoints enforce.
        $entitlements = app(EntitlementService::class);
        $enrollments->each(function ($enrollment) use ($entitlements): void {
            $enrollment->course->videos->each(function ($video) use ($entitlements): void {
                $video->is_free_available = $entitlements->isFree($video);
            });
            $enrollment->course->notes->each(function ($note) use ($entitlements): void {
                $note->is_free_available = $entitlements->isFree($note);
            });
        });

        // Enrolled Course IDs
        $enrolledCourseIds = $enrollments->pluck('course_id')->all();

        // Recommended / Available Other Courses
        $availableCourses = Course::query()
            ->with(['subject', 'author', 'reviewer'])
            ->published()
            ->whereNotIn('id', $enrolledCourseIds)
            ->orderBy('sort_order')
            ->orderBy('id') // deterministic ordering (D-6)
            ->limit(4)
            ->get();

        // Due Flashcards for today
        $dueSchedules = UserFlashcardSchedule::query()
            ->where('user_id', $user->id)
            ->where('due_at', '<=', now())
            ->with('flashcard.deck.course')
            ->orderBy('due_at')
            ->limit(10)
            ->get();

        $dueFlashcardsCount = UserFlashcardSchedule::query()
            ->where('user_id', $user->id)
            ->where('due_at', '<=', now())
            ->count();

        $totalReviewsCount = FlashcardReview::query()
            ->where('user_id', $user->id)
            ->count();

        // Recent Quiz Attempts
        $recentAttempts = $user->quizAttempts()
            ->with('quiz.course')
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        // Video Progress
        $watchedVideosCount = $user->videoProgress()
            ->whereNotNull('completed_at')
            ->count();

        $recentProgress = $user->videoProgress()
            ->with('video.course.subject')
            ->latest('last_watched_at')
            ->limit(3)
            ->get();

        // Active Subscription details (single source of truth — see
        // User::activeSubscription(); never re-implement the expiry logic).
        $activeSubscription = $user->activeSubscription();

        return view('learner.dashboard', [
            'user' => $user,
            'enrollments' => $enrollments,
            'availableCourses' => $availableCourses,
            'dueSchedules' => $dueSchedules,
            'dueFlashcardsCount' => $dueFlashcardsCount,
            'totalReviewsCount' => $totalReviewsCount,
            'recentAttempts' => $recentAttempts,
            'completedVideos' => $watchedVideosCount,
            'recentProgress' => $recentProgress,
            'hasSubscription' => (bool) $activeSubscription,
            'activeSubscription' => $activeSubscription,
        ]);
    }
}
