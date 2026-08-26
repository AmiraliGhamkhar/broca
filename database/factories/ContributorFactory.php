<?php

namespace Database\Factories;

use App\Models\Contributor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contributor> */
class ContributorFactory extends Factory
{
    protected $model = Contributor::class;

    public function definition(): array
    {
        return ['name' => fake()->name(), 'slug' => fake()->unique()->slug(), 'credentials' => 'پروفایل نمونه', 'specialty' => 'تخصص نمونه', 'bio' => 'متن نمونهٔ قابل جایگزینی.', 'is_visible' => true];
    }
}
