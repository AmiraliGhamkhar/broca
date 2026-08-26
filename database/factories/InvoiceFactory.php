<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'number' => 'INV-' . str_pad((string) fake()->unique()->randomNumber(6), 6, '0', STR_PAD_LEFT),
            'amount_irr' => fake()->numberBetween(100000, 1000000),
            'currency' => 'IRR',
            'status' => Invoice::STATUS_PENDING,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function initiated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_INITIATED,
            'authority' => 'A' . fake()->unique()->randomNumber(8),
        ]);
    }
}
