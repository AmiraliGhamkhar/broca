<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory()->published(),
            'title' => $this->faker->sentence(3),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->paragraph(),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'duration_seconds' => $this->faker->numberBetween(30, 3600),
            'manifest_reference' => 'sample-video.mp4',
            'is_free_designated' => false,
            'status' => 'published',
            'published_at' => now()->subDay(),
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