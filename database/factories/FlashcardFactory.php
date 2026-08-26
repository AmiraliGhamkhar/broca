<?php

namespace Database\Factories;

use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Flashcard> */
class FlashcardFactory extends Factory
{
    protected $model = Flashcard::class;

    public function definition(): array
    {
        return [
            'flashcard_deck_id' => FlashcardDeck::factory(),
            'front' => 'مفهوم پزشکی نمونه: '.fake()->sentence(3),
            'back' => fake()->paragraph(),
            'hint' => null,
            'sort_order' => 0,
            'is_free_designated' => false,
            'status' => 'published',
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
