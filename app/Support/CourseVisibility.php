<?php

namespace App\Support;

use App\Models\Flashcard;
use App\Models\Note;
use App\Models\QuizQuestion;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Constrains a content-item query to items that a visitor can actually
 * reach: the parent course must itself be published (status, publish date
 * in the past, and not soft-deleted — all enforced by Course::published()).
 *
 * Free-cap accounting must count what a free visitor can actually consume.
 * Counting items whose course is archived or soft-deleted would let
 * invisible content silently eat the global free quota: legitimate free
 * views get denied fail-closed, and admins are blocked from designating
 * new free items by content nobody can see.
 */
class CourseVisibility
{
    public static function onPublishedCourses(Builder $query): Builder
    {
        return match ($query->getModel()::class) {
            Video::class, Note::class => $query->whereHas('course', fn (Builder $c): Builder => $c->published()),
            Flashcard::class => $query->whereHas('deck.course', fn (Builder $c): Builder => $c->published()),
            QuizQuestion::class => $query->whereHas('quiz.course', fn (Builder $c): Builder => $c->published()),
            default => throw new RuntimeException('CourseVisibility: unsupported model '.$query->getModel()::class),
        };
    }
}
