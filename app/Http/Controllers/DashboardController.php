<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardReview;
use App\Models\UserFlashcardSchedule;
use App\Models\VideoProgress;
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
                'course.notes' => fn ($q) => $q->where('status', 'published')->where('published_at', '<=', now())->orderBy('sort_order'),
                'course.decks' => fn ($q) => $q->where('status', 'published')->where('published_at', '<=', now())->withCount('cards'),
                'course.quizzes' => fn ($q) => $q->where('status', 'published')->where('published_at', '<=', now()),
            ])
            ->latest('enrolled_at')
            ->get();

        // Enrolled Course IDs
        $enrolledCourseIds = $enrollments->pluck('course_id')->all();

        // Recommended / Available Other Courses
        $availableCourses = Course::query()
            ->with(['subject', 'author', 'reviewer'])
            ->published()
            ->whereNotIn('id', $enrolledCourseIds)
            ->orderBy('sort_order')
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

        // Active Subscription details
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->whereNotNull('activated_at')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->with('plan')
            ->first();

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
            'hasSubscription' => $user->hasActiveSubscription(),
            'activeSubscription' => $activeSubscription,
        ]);
    }
}
