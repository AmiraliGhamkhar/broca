<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FreeItemDesignationService
{
    public function set(Model $item, bool $designated): void
    {
        $contentType = $this->getContentType($item);

        DB::transaction(function () use ($item, $designated, $contentType) {
            if ($designated) {
                $this->ensureWithinQuota($contentType);
            }

            $item->update(['is_free_designated' => $designated]);

            Cache::forget("free_cap_" . get_class($item));
        });
    }

    protected function ensureWithinQuota(string $contentType): void
    {
        $caps = [
            'videos' => EntitlementService::FREE_VIDEO_CAP,
            'notes' => EntitlementService::FREE_NOTE_CAP,
            'flashcards' => EntitlementService::FREE_FLASHCARD_CAP,
            'quiz_questions' => EntitlementService::FREE_QUIZ_QUESTION_CAP,
        ];

        $currentCount = DB::table($contentType)
            ->where('is_free_designated', true)
            ->where('status', 'published')
            ->count();

        if ($currentCount >= $caps[$contentType]) {
            throw new RuntimeException("ظرفیت محتوای رایگان برای این بخش تکمیل شده است ({$caps[$contentType]}).");
        }
    }

    protected function getContentType(Model $item): string
    {
        return match (get_class($item)) {
            \App\Models\Video::class => 'videos',
            \App\Models\Note::class => 'notes',
            \App\Models\Flashcard::class => 'flashcards',
            \App\Models\QuizQuestion::class => 'quiz_questions',
            default => throw new RuntimeException("Unsupported content type for free designation."),
        };
    }
}
