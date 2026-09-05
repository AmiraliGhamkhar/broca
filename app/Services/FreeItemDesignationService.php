<?php

namespace App\Services;

use App\Models\Flashcard;
use App\Models\Note;
use App\Models\QuizQuestion;
use App\Models\Video;
use App\Support\CourseVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FreeItemDesignationService
{
    public function set(Model $item, bool $designated): void
    {
        $contentType = $this->getContentType($item);

        // Serialize designations across admin requests so two concurrent
        // "mark as free" clicks can never both pass the cap check.
        $lock = Cache::lock('free-item-designation-'.$contentType, 10);

        try {
            $lock->block(5);

            DB::transaction(function () use ($item, $designated, $contentType): void {
                if ($designated) {
                    $this->ensureWithinQuota($contentType);
                }

                $item->update(['is_free_designated' => $designated]);

                Cache::forget(EntitlementService::freeCapKey(get_class($item)));

            });
        } finally {
            optional($lock)->release();
        }
    }

    protected function ensureWithinQuota(string $contentType): void
    {
        $caps = [
            'videos' => EntitlementService::FREE_VIDEO_CAP,
            'notes' => EntitlementService::FREE_NOTE_CAP,
            'flashcards' => EntitlementService::FREE_FLASHCARD_CAP,
            'quiz_questions' => EntitlementService::FREE_QUIZ_QUESTION_CAP,
        ];

        // Count only items on published courses — identical semantics to the
        // runtime check in EntitlementService, so an admin can always
        // designate up to the cap of items a visitor can actually see.
        $query = match ($contentType) {
            'videos' => Video::query(),
            'notes' => Note::query(),
            'flashcards' => Flashcard::query(),
            'quiz_questions' => QuizQuestion::query(),
            default => throw new RuntimeException('Unsupported content type for free designation.'),
        };

        $currentCount = CourseVisibility::onPublishedCourses($query)
            ->published()
            ->where('is_free_designated', true)
            ->count();

        if ($currentCount >= $caps[$contentType]) {
            throw new RuntimeException("ظرفیت محتوای رایگان برای این بخش تکمیل شده است ({$caps[$contentType]}).");
        }
    }

    protected function getContentType(Model $item): string
    {
        return match (get_class($item)) {
            Video::class => 'videos',
            Note::class => 'notes',
            Flashcard::class => 'flashcards',
            QuizQuestion::class => 'quiz_questions',
            default => throw new RuntimeException('Unsupported content type for free designation.'),
        };
    }
}
