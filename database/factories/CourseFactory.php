<?php

namespace Database\Factories;

use App\Models\Contributor;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    protected $model = Course::class;
    public function definition(): array
    {
        return ['subject_id' => Subject::factory(), 'title' => fake()->sentence(4), 'slug' => fake()->unique()->slug(), 'excerpt' => fake()->sentence(), 'description' => fake()->paragraph(), 'status' => 'draft', 'author_id' => Contributor::factory(), 'reviewer_id' => Contributor::factory(), 'sort_order' => 0];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }
}
