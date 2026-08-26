<?php

namespace Tests\Feature;

use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserFlashcardSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashcardReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewing_a_card_creates_schedule_and_log(): void
    {
        $user = User::factory()->create();
        $deck = FlashcardDeck::factory()->create(); // published course + deck

        $user->enrollments()->create([
            'course_id' => $deck->course_id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'activated_at' => now(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $card = Flashcard::factory()->for($deck)->create();

        $response = $this->actingAs($user)->postJson(route('flashcards.review', $card), ['quality' => 4]);

        $response->assertOk()->assertJsonStructure(['due_at', 'interval_days']);

        $this->assertDatabaseHas('user_flashcard_schedules', [
            'user_id' => $user->id,
            'flashcard_id' => $card->id,
            'repetition_count' => 1,
            'interval_days' => 1,
        ]);
        $this->assertDatabaseHas('flashcard_reviews', [
            'user_id' => $user->id,
            'flashcard_id' => $card->id,
            'quality' => 4,
            'new_interval_days' => 1,
        ]);
    }

    public function test_replaying_a_review_updates_the_same_schedule(): void
    {
        $user = User::factory()->create();
        $deck = FlashcardDeck::factory()->create();

        $user->enrollments()->create(['course_id' => $deck->course_id, 'enrolled_at' => now(), 'status' => 'active']);
        Subscription::factory()->create(['user_id' => $user->id, 'status' => 'active', 'activated_at' => now(), 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);

        $card = Flashcard::factory()->for($deck)->create();

        $this->actingAs($user)->postJson(route('flashcards.review', $card), ['quality' => 4]);
        $this->actingAs($user)->postJson(route('flashcards.review', $card), ['quality' => 4]);

        $this->assertSame(1, UserFlashcardSchedule::where('user_id', $user->id)->where('flashcard_id', $card->id)->count());
        $this->assertSame(6, UserFlashcardSchedule::where('user_id', $user->id)->where('flashcard_id', $card->id)->first()->interval_days);
    }

    public function test_quality_is_validated(): void
    {
        $user = User::factory()->create();
        $deck = FlashcardDeck::factory()->create();
        $user->enrollments()->create(['course_id' => $deck->course_id, 'enrolled_at' => now(), 'status' => 'active']);
        Subscription::factory()->create(['user_id' => $user->id, 'status' => 'active', 'activated_at' => now(), 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);

        $card = Flashcard::factory()->for($deck)->create();

        $this->actingAs($user)->postJson(route('flashcards.review', $card), ['quality' => 9])
            ->assertStatus(422);
    }
}
