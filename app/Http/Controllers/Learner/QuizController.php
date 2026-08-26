<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Policies\ContentPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function show(Request $request, Quiz $quiz): View
    {
        abort_unless($this->published($quiz), 404);
        abort_unless($request->user()->enrollments()->where('course_id', $quiz->course_id)->where('status', 'active')->exists(), 403);

        $questions = $quiz->questions()->with('options')->where('status', 'published')->where('published_at', '<=', now())->orderBy('sort_order')->get()
            ->filter(fn (QuizQuestion $question): bool => app(ContentPolicy::class)->viewQuizQuestion($request->user(), $question));

        return view('learner.quiz', compact('quiz', 'questions'));
    }

    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        abort_unless($this->published($quiz), 404);
        abort_unless($request->user()->enrollments()->where('course_id', $quiz->course_id)->where('status', 'active')->exists(), 403);
        $questions = $quiz->questions()->with('options')->where('status', 'published')->where('published_at', '<=', now())->get()
            ->filter(fn (QuizQuestion $question): bool => app(ContentPolicy::class)->viewQuizQuestion($request->user(), $question));
        abort_unless($questions->isNotEmpty(), 422);
        $answers = $request->input('answers', []);
        abort_unless(is_array($answers), 422);
        $allowedQuestionIds = $questions->keys()->map(fn ($key) => (string) $key)->all();
        $submittedQuestionIds = array_map('strval', array_keys($answers));
        abort_unless(count(array_diff($submittedQuestionIds, $allowedQuestionIds)) === 0, 422);

        $attempt = DB::transaction(function () use ($request, $quiz, $questions, $answers): QuizAttempt {
            $correct = 0;
            $attempt = QuizAttempt::create(['user_id' => $request->user()->id, 'quiz_id' => $quiz->id, 'score_percent' => 0, 'correct_count' => 0, 'question_count' => $questions->count(), 'passed' => false, 'started_at' => now(), 'submitted_at' => now()]);
            foreach ($questions as $question) {
                $selected = $question->options->firstWhere('id', (int) ($answers[$question->id] ?? 0));
                $isCorrect = (bool) $selected?->is_correct;
                $correct += $isCorrect ? 1 : 0;
                QuizAttemptAnswer::create(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $question->id, 'selected_option_id' => $selected?->id, 'is_correct' => $isCorrect]);
            }
            $score = $questions->count() ? (int) floor($correct / $questions->count() * 100) : 0;
            $attempt->update(['score_percent' => $score, 'correct_count' => $correct, 'passed' => $score >= ($quiz->pass_threshold_percent ?: 70)]);
            return $attempt;
        });

        return redirect()->route('quizzes.attempts.show', [$quiz, $attempt]);
    }

    public function result(Request $request, Quiz $quiz, QuizAttempt $attempt): View
    {
        abort_unless($attempt->quiz_id === $quiz->id && $attempt->user_id === $request->user()->id, 404);
        return view('learner.quiz-result', compact('quiz', 'attempt'));
    }

    private function published(Quiz $quiz): bool
    {
        return $quiz->status === 'published' && $quiz->published_at?->isPast() && $quiz->course?->status === 'published' && $quiz->course->published_at?->isPast();
    }
}
