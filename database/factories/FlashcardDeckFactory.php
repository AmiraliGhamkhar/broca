<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\FlashcardDeck;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FlashcardDeck> */
class FlashcardDeckFactory extends Factory
{
    protected $model = FlashcardDeck::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory()->published(),
            'title' => 'دستهٔ کارت: '.fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'sort_order' => 0,
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
