<?php

namespace App\Services;

use App\Models\QuizQuestion;
use App\Models\Flashcard;
use App\Models\Note;
use App\Models\Video;
use App\Models\User;
use App\Support\CourseVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * The single decision point for freemium entitlement. ContentPolicy (and any
 * future gate) must delegate here instead of re-implementing the rules.
 *
 * Locked product decision: exactly 2 free videos, 1 free note, 10 free
 * flashcards and 1 free quiz question globally — everything else requires an
 * active subscription.
 */
class EntitlementService
{
    public const FREE_VIDEO_CAP = 2;
    public const FREE_NOTE_CAP = 1;
    public const FREE_FLASHCARD_CAP = 10;
    public const FREE_QUIZ_QUESTION_CAP = 1;

    public static function freeCapKey(string $model): string
    {
        return 'free_cap_'.str_replace('\\', '_', $model);
    }

    /** Cap per content type, keyed by class basename. */
    private const CAPS = [
        Video::class => self::FREE_VIDEO_CAP,
        Note::class => self::FREE_NOTE_CAP,
        Flashcard::class => self::FREE_FLASHCARD_CAP,
        QuizQuestion::class => self::FREE_QUIZ_QUESTION_CAP,
    ];

    /**
     * Can the user access this item? Either through an active subscription
     * (single source of truth: User::hasActiveSubscription) or because the
     * item is free-designated and the global free cap is respected.
     */
    public function canAccess(User $user, Model $item): bool
    {
        return $user->hasActiveSubscription() || $this->isFree($item);
    }

    /**
     * Is the item designated free AND within the global cap?
     */
    public function isFree(Model $item): bool
    {
        if (! array_key_exists($item::class, self::CAPS)) {
            return false;
        }

        if (! $item->is_free_designated) {
            return false;
        }

        return $this->freeCapNotExceeded($item);
    }

    /**
     * Fail-closed cap check: if more items are designated free than the cap
     * allows (e.g. a race or a direct DB edit), NONE of them are served free.
     *
     * The count only includes items that a visitor can actually reach —
     * items whose course is archived or soft-deleted no longer consume the
     * cap the moment the course stops being published.
     */
    protected function freeCapNotExceeded(Model $item): bool
    {
        $model = $item::class;
        $cacheKey = self::freeCapKey($model);

        $count = Cache::remember(
            $cacheKey,
            300,
            fn () => CourseVisibility::onPublishedCourses($model::query())
                ->published()
                ->where('is_free_designated', true)
                ->count()
        );

        return $count <= self::CAPS[$model];
    }
}
