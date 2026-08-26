<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
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
    public function study(Request $request, FlashcardDeck $deck): View
    {
        abort_unless($this->publishedDeck($deck), 404);
        abort_unless($request->user()->isEnrolledIn($deck->course_id), 403);

        // Eager-load deck.course (ContentPolicy walks card→deck→course).
        $cards = $deck->cards()
            ->with('deck.course')
            ->where('status', 'published')
            ->where('published_at', '<=', now())
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
        return $deck->status === 'published'
            && $deck->published_at?->isPast()
            && $deck->course?->status === 'published'
            && $deck->course->published_at?->isPast();
    }
}
