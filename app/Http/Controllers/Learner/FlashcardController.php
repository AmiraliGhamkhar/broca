<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Flashcard;
use App\Models\FlashcardReview;
use App\Models\FlashcardDeck;
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
        abort_unless($request->user()->enrollments()->where('course_id', $deck->course_id)->where('status', 'active')->exists(), 403);

        $cards = $deck->cards()->where('status', 'published')->where('published_at', '<=', now())->orderBy('sort_order')->get()
            ->filter(fn (Flashcard $card): bool => app(ContentPolicy::class)->viewFlashcard($request->user(), $card));

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
                $schedule = UserFlashcardSchedule::create([
                    'user_id' => $user->id,
                    'flashcard_id' => $flashcard->id,
                    'state' => 'new',
                    'ease_factor' => 2.5,
                    'interval_days' => 0,
                    'repetition_count' => 0,
                    'due_at' => now(),
                ]);
            }
            $change = $srs->review($schedule, (int) $data['quality']);
            FlashcardReview::create($change + ['user_id' => $user->id, 'flashcard_id' => $flashcard->id, 'schedule_id' => $schedule->id, 'quality' => $data['quality'], 'reviewed_at' => now()]);

            $freshSchedule = $schedule->fresh();

            return ['due_at' => $freshSchedule?->due_at?->toIso8601String(), 'interval_days' => $freshSchedule?->interval_days];
        });

        return response()->json($result);
    }

    private function publishedDeck(FlashcardDeck $deck): bool
    {
        return $deck->status === 'published' && $deck->published_at?->isPast() && $deck->course?->status === 'published' && $deck->course->published_at?->isPast();
    }
}
