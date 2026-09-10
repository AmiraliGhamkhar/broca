<?php

namespace App\Policies;

use App\Models\Flashcard;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\Video;
use App\Services\EntitlementService;
use Illuminate\Database\Eloquent\Model;

/**
 * Content gating policy. Frequent per-user checks (subscription, enrollment)
 * are memoized on the User instance, and controllers eager-load the
 * question→quiz→course / card→deck→course chains, so filtering a page of
 * items does not degrade into an N+1.
 */
class ContentPolicy
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function viewVideo(User $user, Video $video): bool
    {
        return $this->canView($user, $video);
    }

    public function viewNote(User $user, Note $note): bool
    {
        return $this->canView($user, $note);
    }

    public function viewFlashcard(User $user, Flashcard $flashcard): bool
    {
        return $this->canView($user, $flashcard);
    }

    public function viewQuizQuestion(User $user, QuizQuestion $question): bool
    {
        return $this->canView($user, $question);
    }

    private function canView(User $user, Model $content): bool
    {
        if (! $user->isActive() || ! $user->hasVerifiedEmail()) {
            return false;
        }

        $course = $this->courseFor($content);
        if (! $course || ! $this->isPublished($course) || ! $this->isPublished($content)) {
            return false;
        }

        if (! $user->isEnrolledIn($course->id)) {
            return false;
        }

        // Freemium decision delegated to the single entitlement service:
        // active subscription OR free-designated within the global cap.
        return $this->entitlements->canAccess($user, $content);
    }

    private function courseFor(Model $content): ?object
    {
        if ($content instanceof Video || $content instanceof Note || $content instanceof Quiz) {
            return $content->course;
        }

        if ($content instanceof Flashcard) {
            return $content->deck?->course;
        }

        if ($content instanceof QuizQuestion) {
            return $content->quiz?->course;
        }

        return null;
    }

    private function isPublished(Model $model): bool
    {
        return $model->status === 'published' && $model->published_at?->isPast();
    }
}
