<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\Note;
use App\Models\QuizQuestion;
use App\Models\Video;
use App\Services\EntitlementService;
use Illuminate\Support\Facades\Cache;

/**
 * Free-cap counts are a function of course visibility (an item only consumes
 * the cap while its course is published), so a course's own status change,
 * publish date, or deletion invalidates the cached counts for every item
 * class. FreeCapObserver covers the item side; this covers the course side.
 */
class CourseFreeCapObserver
{
    public function saved(Course $course): void
    {
        if ($course->wasChanged(['status', 'published_at', 'deleted_at'])) {
            $this->invalidateAll();
        }
    }

    public function deleted(Course $course): void
    {
        $this->invalidateAll();
    }

    private function invalidateAll(): void
    {
        foreach ([Video::class, Note::class, Flashcard::class, QuizQuestion::class] as $model) {
            Cache::forget(EntitlementService::freeCapKey($model));
        }
    }
}
