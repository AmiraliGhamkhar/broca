<?php

namespace Tests\Unit;

use App\Models\UserFlashcardSchedule;
use App\Services\SrsService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class SrsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Eloquent models resolve their date format through the connection
        // grammar, so even these pure-logic tests need a resolver. A capsule
        // sqlite connection provides one without booting the framework.
        $capsule = new Capsule;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        Model::setConnectionResolver($capsule->getDatabaseManager());
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();

        parent::tearDown();
    }

    public function test_first_successful_review_schedules_one_day_ahead(): void
    {
        $result = (new SrsService)->review($this->schedule(), 4);

        $this->assertSame(1, $result['change']['new_interval_days']);
        $this->assertSame(1, $result['next']['repetition_count']);
        $this->assertSame('review', $result['next']['state']);
    }

    public function test_second_successful_review_schedules_six_days_ahead(): void
    {
        $result = (new SrsService)->review($this->schedule(['repetition_count' => 1, 'interval_days' => 1]), 4);

        $this->assertSame(6, $result['change']['new_interval_days']);
    }

    public function test_lapse_resets_repetitions_and_ease_floor_is_enforced(): void
    {
        $result = (new SrsService)->review($this->schedule(['repetition_count' => 3, 'interval_days' => 20, 'ease_factor' => 1.31]), 0);

        $this->assertSame(0, $result['next']['repetition_count']);
        $this->assertSame(1, $result['change']['new_interval_days']);
        $this->assertSame('learning', $result['next']['state']);
        $this->assertGreaterThanOrEqual(1.3, $result['change']['new_ease_factor']);
    }

    public function test_quality_five_increases_ease_factor(): void
    {
        $result = (new SrsService)->review($this->schedule(['ease_factor' => 2.5]), 5);

        $this->assertEqualsWithDelta(2.6, $result['change']['new_ease_factor'], 0.001);
    }

    public function test_interval_grows_by_ease_after_two_repetitions(): void
    {
        $result = (new SrsService)->review($this->schedule(['repetition_count' => 2, 'interval_days' => 6, 'ease_factor' => 2.5]), 4);

        $this->assertSame(15, $result['change']['new_interval_days']); // round(6 * 2.5)
    }

    public function test_due_date_moves_forward_by_the_new_interval(): void
    {
        $result = (new SrsService)->review($this->schedule(['repetition_count' => 2, 'interval_days' => 6, 'ease_factor' => 2.5]), 4);

        $this->assertEqualsWithDelta(now()->addDays(15)->timestamp, $result['next']['due_at']->timestamp, 5);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function schedule(array $attributes = []): UserFlashcardSchedule
    {
        return new UserFlashcardSchedule(array_merge([
            'user_id' => 1,
            'flashcard_id' => 1,
            'state' => 'new',
            'ease_factor' => 2.5,
            'interval_days' => 0,
            'repetition_count' => 0,
            'due_at' => now(),
        ], $attributes));
    }
}
