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
    /**
     * Learner quiz hub: published quizzes of the user's enrolled, published
     * courses with per-quiz attempt stats (tries, best score, last attempt).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $enrolledCourseIds = $user->enrollments()
            ->where('status', 'active')
            ->pluck('course_id');

        $quizzes = Quiz::query()
            ->with('course.subject')
            ->withCount(['questions' => fn ($q) => $q->published()])
            ->published()
            ->whereHas('course', fn ($q) => $q->published()->whereIn('id', $enrolledCourseIds))
            ->orderBy('id')
            ->get();

        $attempts = $quizzes->isEmpty()
            ? collect()
            : QuizAttempt::query()
                ->where('user_id', $user->id)
                ->whereIn('quiz_id', $quizzes->pluck('id'))
                ->get()
                ->groupBy('quiz_id');

        $quizStats = $attempts->map(fn ($attempts) => [
            'tries' => $attempts->count(),
            'best' => $attempts->max('score_percent'),
            'last' => $attempts->sortByDesc('submitted_at')->first(),
        ]);

        return view('learner.quizzes', [
            'quizzes' => $quizzes,
            'quizStats' => $quizStats,
            'hasEnrollments' => $enrolledCourseIds->isNotEmpty(),
        ]);
    }

    public function show(Request $request, Quiz $quiz): View
    {
        abort_unless($this->published($quiz), 404);
        abort_unless($request->user()->isEnrolledIn($quiz->course_id), 403);

        // Eager-load options AND quiz.course (ContentPolicy walks
        // question→quiz→course) so filtering N questions costs O(1) queries.
        $questions = $quiz->questions()
            ->with(['options', 'quiz.course'])
            ->published()
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (QuizQuestion $question): bool => app(ContentPolicy::class)->viewQuizQuestion($request->user(), $question))
            ->values();

        return view('learner.quiz', compact('quiz', 'questions'));
    }

    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        abort_unless($this->published($quiz), 404);
        abort_unless($request->user()->isEnrolledIn($quiz->course_id), 403);

        $questions = $quiz->questions()
            // quiz.course must ride along: ContentPolicy walks
            // question→quiz→course per item, and without it every filtered
            // question costs two extra queries (D-7 — same eager-load the
            // show() path one method up already uses).
            ->with(['options', 'quiz.course'])
            ->published()
            ->get()
            ->filter(fn (QuizQuestion $question): bool => app(ContentPolicy::class)->viewQuizQuestion($request->user(), $question))
            ->values();

        abort_unless($questions->isNotEmpty(), 422);

        $answers = $request->input('answers', []);
        abort_unless(is_array($answers), 422);

        // The form posts answers keyed by question ID, so the allow-list must
        // be the question IDs (modelKeys), NOT the collection's 0..n-1 keys.
        $allowedQuestionIds = $questions->modelKeys();
        $submittedQuestionIds = array_map('intval', array_keys($answers));
        abort_unless(count(array_diff($submittedQuestionIds, $allowedQuestionIds)) === 0, 422);

        $attempt = DB::transaction(function () use ($request, $quiz, $questions, $answers): QuizAttempt {
            $correct = 0;
            $attempt = QuizAttempt::create([
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz->id,
                'score_percent' => 0,
                'correct_count' => 0,
                'question_count' => $questions->count(),
                'passed' => false,
                'started_at' => now(),
                'submitted_at' => now(),
            ]);

            foreach ($questions as $question) {
                $selectedOptionId = isset($answers[$question->id]) ? (int) $answers[$question->id] : 0;
                // The selected option must belong to the question being graded.
                $selected = $question->options->firstWhere('id', $selectedOptionId);
                $isCorrect = (bool) $selected?->is_correct;
                $correct += $isCorrect ? 1 : 0;
                QuizAttemptAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $question->id,
                    'selected_option_id' => $selected?->id,
                    'is_correct' => $isCorrect,
                ]);
            }

            $score = $questions->count() ? (int) floor($correct / $questions->count() * 100) : 0;
            $attempt->update([
                'score_percent' => $score,
                'correct_count' => $correct,
                'passed' => $score >= ($quiz->pass_threshold_percent ?: 70),
            ]);

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
        return $quiz->isPublished() && $quiz->course?->isPublished() === true;
    }
}
