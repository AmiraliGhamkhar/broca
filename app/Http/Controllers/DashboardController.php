<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();

        $enrollments = $user->enrollments()
            ->with(['course.subject'])
            ->latest('enrolled_at')
            ->get();

        $dueFlashcards = $user->flashcardSchedules()
            ->where('due_at', '<=', now())
            ->count();

        $recentAttempts = $user->quizAttempts()
            ->with('quiz')
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        $watchedVideos = $user->videoProgress()
            ->whereNotNull('completed_at')
            ->count();

        return view('learner.dashboard', [
            'enrollments' => $enrollments,
            'dueFlashcards' => $dueFlashcards,
            'recentAttempts' => $recentAttempts,
            'completedVideos' => $watchedVideos,
            'hasSubscription' => $user->hasActiveSubscription(),
        ]);
    }
}
