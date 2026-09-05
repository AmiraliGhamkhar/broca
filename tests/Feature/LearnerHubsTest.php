<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerHubsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function enroll(Course $course): void
    {
        $this->user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
    }

    public function test_flashcards_hub_lists_decks_with_due_counts(): void
    {
        $course = Course::factory()->published()->create();
        $deck = FlashcardDeck::factory()->for($course)->create(['title' => 'کارت‌های قلب']);
        $this->enroll($course);

        $dueCard = Flashcard::factory()->for($deck, 'deck')->create(['is_free_designated' => true]);
        Flashcard::factory()->for($deck, 'deck')->create(['is_free_designated' => true]);

        $this->user->flashcardSchedules()->create([
            'flashcard_id' => $dueCard->id,
            'state' => 'review',
            'ease_factor' => 2.5,
            'interval_days' => 1,
            'repetition_count' => 1,
            'due_at' => now()->subHour(),
            'last_reviewed_at' => now()->subDay(),
        ]);

        $this->actingAs($this->user)
            ->get(route('flashcards.index'))
            ->assertOk()
            ->assertSee('کارت‌های قلب')
            ->assertSee('1 دستهٔ کارت')
            ->assertSee('1 کارت آمادهٔ مرور امروز')
            ->assertSee(route('decks.study', [$course, $deck]));
    }

    public function test_flashcards_hub_is_empty_without_enrollments(): void
    {
        $this->actingAs($this->user)
            ->get(route('flashcards.index'))
            ->assertOk()
            ->assertSee('برای شروع، در یک دوره ثبت‌نام کن');
    }

    public function test_quizzes_hub_shows_best_score_after_an_attempt(): void
    {
        $course = Course::factory()->published()->create();
        $quiz = Quiz::factory()->for($course)->create(['title' => 'آزمون فصل اول']);
        $this->enroll($course);

        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'quiz_id' => $quiz->id,
            'score_percent' => 80,
            'correct_count' => 1,
            'question_count' => 1,
            'passed' => true,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('quizzes.index'))
            ->assertOk()
            ->assertSee('آزمون فصل اول')
            ->assertSee('بهترین نتیجه: 80٪')
            ->assertSee('1 تلاش')
            ->assertSee('تلاش دوباره');
    }

    public function test_dashboard_shows_continue_watching_and_subscription_state(): void
    {
        $course = Course::factory()->published()->create();
        $video = Video::factory()->for($course)->create(['is_free_designated' => true, 'title' => 'ساختار غشای سلولی']);
        $this->enroll($course);

        VideoProgress::create([
            'user_id' => $this->user->id,
            'video_id' => $video->id,
            'watched_seconds' => 500,
            'watched_percent' => 50,
            'last_watched_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ادامهٔ تماشا')
            ->assertSee('ساختار غشای سلولی')
            ->assertSee('50٪ دیده‌شده')
            ->assertSee('رایگان');
    }

    public function test_branded_404_page_renders_for_unknown_routes(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertStatus(404)
            ->assertSee('۴۰۴')
            ->assertSee('این صفحه اینجا نیست');
    }

    public function test_branded_403_page_renders_for_policy_denials(): void
    {
        $course = Course::factory()->published()->create();
        $video = Video::factory()->for($course)->create(['is_free_designated' => false]);
        $this->enroll($course); // enrolled but no subscription and not free

        $this->actingAs($this->user)
            ->get(route('videos.playback', $video))
            ->assertStatus(403)
            ->assertSee('۴۰۳')
            ->assertSee('دسترسی به این بخش برای شما مجاز نیست');
    }
}
