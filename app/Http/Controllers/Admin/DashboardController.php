<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Video;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'users' => User::count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'courses' => Course::count(),
            'publishedCourses' => Course::where('status', 'published')->count(),
            'subjects' => Subject::count(),
            'videos' => Video::count(),
            'notes' => Note::count(),
            'decks' => FlashcardDeck::count(),
            'flashcards' => Flashcard::count(),
            'quizzes' => Quiz::count(),
            'activeSubscriptions' => Subscription::query()
                ->where('status', 'active')
                ->whereNotNull('activated_at')
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->count(),
            'paidInvoices' => Invoice::where('status', 'paid')->count(),
            'revenueIrr' => (int) Invoice::where('status', 'paid')->sum('amount_irr'),
            'recentLogs' => AdminActivityLog::with('user')->latest('id')->limit(8)->get(),
            'recentSubscriptions' => Subscription::with(['user', 'plan'])->latest('id')->limit(6)->get(),
            'recentCourses' => Course::with(['subject', 'author'])->latest('id')->limit(5)->get(),
        ]);
    }
}
