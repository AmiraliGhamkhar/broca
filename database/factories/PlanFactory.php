<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'code' => 'plan-'.fake()->unique()->slug(),
            'name' => fake()->randomElement(['یک‌ماهه', 'سه‌ماهه']),
            'description' => 'دسترسی کامل به همهٔ دوره‌ها',
            'duration_months' => fake()->randomElement([1, 3]),
            'price_irr' => fake()->randomElement([500000, 1200000]),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'free-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'رایگان',
            'duration_months' => 0,
            'price_irr' => 0,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'monthly-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'یک‌ماهه',
            'duration_months' => 1,
            'price_irr' => 500000,
        ]);
    }
}
