<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class EntitlementService
{
    public const FREE_VIDEO_CAP = 2;
    public const FREE_NOTE_CAP = 1;
    public const FREE_FLASHCARD_CAP = 10;
    public const FREE_QUIZ_QUESTION_CAP = 1;

    /**
     * Determine if a user can access a given item (video, note, flashcard, quiz question).
     */
    public function canAccess(User $user, $item): bool
    {
        // Subscription placeholder – always false for now (Phase 3 will implement)
        if (method_exists($user, 'hasActiveSubscription') && $user->hasActiveSubscription()) {
            return true;
        }

        return $this->isFree($item);
    }

    /**
     * Check if the item is designated free and the global cap is not exceeded.
     */
    public function isFree($item): bool
    {
        if (! property_exists($item, 'is_free_designated') || ! $item->is_free_designated) {
            return false;
        }
        return $this->freeCapNotExceeded($item);
    }

    protected function freeCapNotExceeded($item): bool
    {
        $model = get_class($item);
        $cacheKey = "free_cap_{$model}";
        $count = Cache::remember($cacheKey, 300, fn() => $model::where('is_free_designated', true)
            ->where('status', 'published')
            ->count());
        switch (true) {
            case $item instanceof \App\Models\Video:
                return $count <= self::FREE_VIDEO_CAP;
            case $item instanceof \App\Models\Note:
                return $count <= self::FREE_NOTE_CAP;
            case $item instanceof \App\Models\Flashcard:
                return $count <= self::FREE_FLASHCARD_CAP;
            case $item instanceof \App\Models\QuizQuestion:
                return $count <= self::FREE_QUIZ_QUESTION_CAP;
        }
        return false;
    }
}
