<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'gateway' => 'zarinpal',
            'request_payload' => ['amount' => fake()->numberBetween(100000, 1000000), 'description' => 'Test payment'],
            'response_payload' => ['authority' => 'A'.fake()->unique()->randomNumber(8)],
            'status' => 'initiated',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
