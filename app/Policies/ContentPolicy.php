<?php

namespace App\Policies;

use App\Models\CourseEnrollment;
use App\Models\Flashcard;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;

class ContentPolicy
{
    public function viewVideo(User $user, Video $video): bool
    {
        return $this->canView($user, $video, $video->is_free_designated);
    }

    public function viewNote(User $user, Note $note): bool
    {
        return $this->canView($user, $note, $note->is_free_designated);
    }

    public function viewFlashcard(User $user, Flashcard $flashcard): bool
    {
        return $this->canView($user, $flashcard, $flashcard->is_free_designated);
    }

    public function viewQuiz(User $user, Quiz $quiz): bool
    {
        return $this->canView($user, $quiz, false);
    }

    public function viewQuizQuestion(User $user, QuizQuestion $question): bool
    {
        return $this->canView($user, $question, $question->is_free_designated);
    }

    private function canView(User $user, Model $content, bool $isFree): bool
    {
        if (! $user->isActive() || ! $user->hasVerifiedEmail()) {
            return false;
        }

        $course = $this->courseFor($content);
        if (! $course || ! $this->isPublished($course) || ! $this->isEnrolled($user, $course->id) || ! $this->isPublished($content)) {
            return false;
        }

        return $isFree || $user->subscriptions()
            ->where('status', 'active')
            ->whereNotNull('activated_at')
            ->whereNotNull('starts_at')->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })->exists();
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

    private function isEnrolled(User $user, int $courseId): bool
    {
        return $user->enrollments()->where('course_id', $courseId)->where('status', 'active')->exists();
    }
}
