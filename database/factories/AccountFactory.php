<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => fake()->unique()->userName(),
            'instagram_id' => fake()->unique()->numerify('##########'),
            'access_token' => fake()->unique()->sha256(),
            'is_active' => true,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the account has been synced recently.
     */
    public function recentlySynced(): static
    {
        return $this->state(fn () => [
            'last_synced_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
        ]);
    }

    /**
     * Indicate that the account has no access token.
     */
    public function withoutAccessToken(): static
    {
        return $this->state(fn () => [
            'access_token' => null,
        ]);
    }
}
