<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\FlashcardReview;
use App\Models\UserFlashcardSchedule;
use App\Policies\ContentPolicy;
use App\Services\SrsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FlashcardController extends Controller
{
    /**
     * Learner flashcard hub: every published deck belonging to the user's
     * enrolled, published courses, with per-deck due counts for today.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $enrolledCourseIds = $user->enrollments()
            ->where('status', 'active')
            ->pluck('course_id');

        $decks = FlashcardDeck::query()
            ->with('course.subject', 'author', 'reviewer')
            ->withCount(['cards' => fn ($q) => $q->published()])
            ->published()
            ->whereHas('course', fn ($q) => $q->published()->whereIn('id', $enrolledCourseIds))
            ->orderBy('sort_order')
            ->get();

        $dueCounts = $decks->isEmpty()
            ? collect()
            : UserFlashcardSchedule::query()
                ->join('flashcards', 'flashcards.id', '=', 'user_flashcard_schedules.flashcard_id')
                ->where('user_flashcard_schedules.user_id', $user->id)
                ->where('user_flashcard_schedules.due_at', '<=', now())
                ->whereIn('flashcards.flashcard_deck_id', $decks->pluck('id'))
                ->groupBy('flashcards.flashcard_deck_id')
                ->selectRaw('flashcards.flashcard_deck_id as deck_id, count(*) as due')
                ->pluck('due', 'deck_id');

        return view('learner.flashcards', [
            'decks' => $decks,
            'dueCounts' => $dueCounts,
            'dueToday' => $dueCounts->sum(),
            'hasEnrollments' => $enrolledCourseIds->isNotEmpty(),
        ]);
    }

    public function study(Request $request, Course $course, FlashcardDeck $deck): View
    {
        // Same URL-scoping contract as the video/note controllers: the deck
        // must belong to the {course} in the URL, otherwise the breadcrumb
        // context lies and a deck is reachable under a foreign course's URL
        // (Round-6 audit F-3).
        abort_unless($deck->course_id === $course->id && $this->publishedDeck($deck), 404);
        abort_unless($request->user()->isEnrolledIn($deck->course_id), 403);

        // Eager-load deck.course (ContentPolicy walks card→deck→course).
        $cards = $deck->cards()
            ->with('deck.course')
            ->published()
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Flashcard $card): bool => app(ContentPolicy::class)->viewFlashcard($request->user(), $card))
            ->values();

        return view('learner.deck-study', compact('deck', 'cards'));
    }

    public function review(Request $request, Flashcard $flashcard, SrsService $srs): JsonResponse
    {
        abort_unless(app(ContentPolicy::class)->viewFlashcard($request->user(), $flashcard), 403);
        $data = $request->validate(['quality' => ['required', 'integer', 'min:0', 'max:5']]);
        $user = $request->user();

        $result = DB::transaction(function () use ($user, $flashcard, $data, $srs): array {
            $schedule = UserFlashcardSchedule::query()->where('user_id', $user->id)->where('flashcard_id', $flashcard->id)->lockForUpdate()->first();
            if (! $schedule) {
                try {
                    $schedule = UserFlashcardSchedule::create([
                        'user_id' => $user->id,
                        'flashcard_id' => $flashcard->id,
                        'state' => 'new',
                        'ease_factor' => 2.5,
                        'interval_days' => 0,
                        'repetition_count' => 0,
                        'due_at' => now(),
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    $schedule = UserFlashcardSchedule::query()->where('user_id', $user->id)->where('flashcard_id', $flashcard->id)->lockForUpdate()->firstOrFail();
                }
            }

            $change = $srs->apply($schedule, (int) $data['quality']);

            FlashcardReview::create($change + [
                'user_id' => $user->id,
                'flashcard_id' => $flashcard->id,
                'schedule_id' => $schedule->id,
                'quality' => (int) $data['quality'],
                'reviewed_at' => now(),
            ]);

            $freshSchedule = $schedule->fresh();

            return ['due_at' => $freshSchedule?->due_at?->toIso8601String(), 'interval_days' => $freshSchedule?->interval_days];
        });

        return response()->json($result);
    }

    private function publishedDeck(FlashcardDeck $deck): bool
    {
        return $deck->isPublished() && $deck->course?->isPublished() === true;
    }
}
