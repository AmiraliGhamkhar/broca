<?php

namespace App\Services;

use App\Models\UserFlashcardSchedule;
use Carbon\CarbonImmutable;

/**
 * SM-2-compatible scheduler (documented, deterministic). A pure-PHP FSRS
 * implementation can replace this class behind the same method signatures —
 * see DECISIONS.md.
 */
class SrsService
{
    /**
     * Pure computation of one SM-2-style review. Mutates nothing.
     *
     * Quality is 0 (complete failure) through 5 (easy recall).
     *
     * @return array{
     *     change: array{previous_interval_days:int, new_interval_days:int, previous_ease_factor:float, new_ease_factor:float},
     *     next: array{state:string, ease_factor:float, interval_days:int, repetition_count:int, due_at:CarbonImmutable, last_reviewed_at:CarbonImmutable}
     * }
     */
    public function review(UserFlashcardSchedule $schedule, int $quality): array
    {
        $previousInterval = (int) $schedule->interval_days;
        $previousEase = (float) $schedule->ease_factor;
        $ease = max(1.3, $previousEase + 0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));

        if ($quality < 3) {
            $repetitions = 0;
            $interval = 1;
            $state = 'learning';
        } else {
            $repetitions = (int) $schedule->repetition_count + 1;
            $interval = match ($repetitions) {
                1 => 1,
                2 => 6,
                default => max(1, (int) round(max(1, $previousInterval) * $ease)),
            };
            $state = 'review';
        }

        $now = CarbonImmutable::now();

        return [
            'change' => [
                'previous_interval_days' => $previousInterval,
                'new_interval_days' => $interval,
                'previous_ease_factor' => $previousEase,
                'new_ease_factor' => round($ease, 2),
            ],
            'next' => [
                'state' => $state,
                'ease_factor' => round($ease, 2),
                'interval_days' => $interval,
                'repetition_count' => $repetitions,
                'due_at' => $now->addDays($interval),
                'last_reviewed_at' => $now,
            ],
        ];
    }

    /**
     * Compute and persist the review onto the schedule (call inside the
     * controller's transaction). Returns the FlashcardReview columns.
     *
     * @return array{previous_interval_days:int, new_interval_days:int, previous_ease_factor:float, new_ease_factor:float}
     */
    public function apply(UserFlashcardSchedule $schedule, int $quality): array
    {
        $result = $this->review($schedule, $quality);

        $schedule->fill($result['next'])->save();

        return $result['change'];
    }
}
