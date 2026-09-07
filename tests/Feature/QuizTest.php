<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Subscription;
use App\Models\User;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the submission bug where allowed question IDs were
 * compared against the collection's 0..n-1 keys instead of model keys —
 * every submission used to 422.
 */
class QuizTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    private User $user;

    private Quiz $quiz;

    /** @var array<int, QuizQuestion> */
    private array $questions = [];

    /** @var array<int, QuizOption> correct option per question id */
    private array $correctOptions = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $course = \App\Models\Course::factory()->published()->create();
        $this->quiz = Quiz::factory()->published()->create(['course_id' => $course->id]);
        $this->user->enrollments()->create([
            'course_id' => $this->quiz->course_id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'activated_at' => now(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        foreach (range(1, 3) as $i) {
            $question = QuizQuestion::factory()->create(['quiz_id' => $this->quiz->id, 'sort_order' => $i]);
            $correct = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
            QuizOption::factory()->create(['quiz_question_id' => $question->id]);
            $this->questions[] = $question;
            $this->correctOptions[$question->id] = $correct;
        }
    }

    public function test_submitting_answers_keyed_by_question_id_scores_correctly(): void
    {
        $answers = [];
        foreach ($this->questions as $index => $question) {
            // First question answered WRONG (the other option), rest correct.
            $answers[$question->id] = $index === 0
                ? QuizOption::where('quiz_question_id', $question->id)->where('is_correct', false)->first()->id
                : $this->correctOptions[$question->id]->id;
        }

        $response = $this->actingAs($this->user)->post(route('quizzes.attempts.store', $this->quiz), ['answers' => $answers]);

        $attempt = QuizAttempt::query()->latest('id')->first();
        $response->assertRedirect(route('quizzes.attempts.show', [$this->quiz, $attempt]));

        $this->assertSame(2, $attempt->correct_count);
        $this->assertSame(3, $attempt->question_count);
        $this->assertSame(66, $attempt->score_percent);
        $this->assertFalse($attempt->passed); // 66 < default 70

        $this->actingAs($this->user)
            ->get(route('quizzes.attempts.show', [$this->quiz, $attempt]))
            ->assertOk();
    }

    public function test_a_perfect_submission_passes(): void
    {
        $answers = collect($this->correctOptions)->map(fn (QuizOption $option) => $option->id)->all();

        $this->actingAs($this->user)->post(route('quizzes.attempts.store', $this->quiz), ['answers' => $answers]);

        $attempt = QuizAttempt::latest('id')->first();
        $this->assertSame(100, $attempt->score_percent);
        $this->assertTrue($attempt->passed);
    }

    public function test_submissions_are_capped_at_thirty_attempts_per_quiz_per_day(): void
    {
        $answers = collect($this->correctOptions)->map(fn (QuizOption $option) => $option->id)->all();

        // Seed 30 attempts inside the rolling 24h window (the counter the
        // controller enforces is quiz_attempts rows — the route throttle is
        // a separate, coarser defense).
        foreach (range(1, 30) as $i) {
            QuizAttempt::create([
                'user_id' => $this->user->id,
                'quiz_id' => $this->quiz->id,
                'score_percent' => 100,
                'correct_count' => 3,
                'question_count' => 3,
                'passed' => true,
                'started_at' => now()->subMinutes($i),
                'submitted_at' => now()->subMinutes($i),
            ]);
        }

        $this->actingAs($this->user)
            ->post(route('quizzes.attempts.store', $this->quiz), ['answers' => $answers])
            ->assertStatus(429);

        $this->assertSame(30, QuizAttempt::query()->where('user_id', $this->user->id)->where('quiz_id', $this->quiz->id)->count());

        // An attempt 25h ago ages out of the window — the cap must not be
        // a lifetime quota.
        QuizAttempt::query()->update(['submitted_at' => now()->subHours(25), 'started_at' => now()->subHours(25)]);

        $this->actingAs($this->user)
            ->post(route('quizzes.attempts.store', $this->quiz), ['answers' => $answers])
            ->assertRedirect();

        $this->assertSame(31, QuizAttempt::query()->where('user_id', $this->user->id)->where('quiz_id', $this->quiz->id)->count());
    }

    public function test_submitting_an_unknown_question_id_is_rejected(): void
    {
        $answers = [999999 => $this->correctOptions[$this->questions[0]->id]->id];

        $this->actingAs($this->user)
            ->post(route('quizzes.attempts.store', $this->quiz), ['answers' => $answers])
            ->assertStatus(422);
    }

    public function test_submitting_an_option_from_another_question_scores_as_wrong(): void
    {
        $foreignOption = QuizOption::factory()->correct()->create();

        $this->actingAs($this->user)->post(route('quizzes.attempts.store', $this->quiz), [
            'answers' => [$this->questions[0]->id => $foreignOption->id],
        ]);

        $attempt = QuizAttempt::latest('id')->first();
        $this->assertSame(0, $attempt->correct_count);
    }

    public function test_not_enrolled_user_cannot_submit(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post(route('quizzes.attempts.store', $this->quiz), ['answers' => []])
            ->assertForbidden();
    }

    public function test_attempt_results_are_private(): void
    {
        $attempt = QuizAttempt::create([
            'user_id' => $this->user->id,
            'quiz_id' => $this->quiz->id,
            'score_percent' => 100,
            'correct_count' => 3,
            'question_count' => 3,
            'passed' => true,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('quizzes.attempts.show', [$this->quiz, $attempt]))
            ->assertNotFound();
    }
}
