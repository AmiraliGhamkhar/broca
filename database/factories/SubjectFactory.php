<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subject> */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'slug' => fake()->unique()->slug(), 'description' => fake()->sentence(), 'is_visible' => true];
    }
}
