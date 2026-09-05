<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 *
 * is_admin/status are NOT mass-assignable, so states use forceFill after
 * creation — never $fillable.
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('09#########'),
            // Mirror the users.status DB column default on the in-memory
            // model too: actingAs() hands this exact instance to the request,
            // and EnsureActive would otherwise read a NULL status and treat
            // the user as suspended.
            'status' => 'active',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->forceFill(['status' => 'suspended'])->save();
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->forceFill(['is_admin' => true])->save();
        });
    }
}
