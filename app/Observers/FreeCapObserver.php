<?php

namespace App\Observers;

use App\Services\EntitlementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Any change to an item's visibility as free content (status, publish date,
 * free designation, or deletion) invalidates the cached eligible-free list
 * for its class, so the caps are never served stale. Registered for Video,
 * Note, Flashcard and QuizQuestion in AppServiceProvider.
 */
class FreeCapObserver
{
    public function saved(Model $model): void
    {
        if ($model->wasChanged(['status', 'published_at', 'is_free_designated', 'deleted_at'])) {
            $this->invalidate($model);
        }
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        Cache::forget(EntitlementService::freeCapKey($model::class));
    }
}
