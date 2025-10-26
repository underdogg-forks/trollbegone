<?php

namespace Database\Factories;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlockedAccount>
 */
class BlockedAccountFactory extends Factory
{
    protected $model = BlockedAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'instagram_account_id' => InstagramAccount::factory(),
            'blocked_username' => fake()->userName(),
            'blocked_instagram_id' => fake()->numerify('##########'),
            'reason' => fake()->randomElement([
                'Spam',
                'Harassment',
                'Inappropriate content',
                'Repeated violations',
                null,
            ]),
            'comment_text' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the blocked account has no Instagram ID.
     */
    public function withoutInstagramId(): static
    {
        return $this->state(fn () => [
            'blocked_instagram_id' => null,
        ]);
    }

    /**
     * Indicate that the blocked account has a specific reason.
     */
    public function withReason(string $reason): static
    {
        return $this->state(fn () => [
            'reason' => $reason,
        ]);
    }

    /**
     * Indicate that the blocked account has comment text.
     */
    public function withComment(string $comment): static
    {
        return $this->state(fn () => [
            'comment_text' => $comment,
        ]);
    }

    /**
     * Indicate that the blocked account belongs to a specific Instagram account.
     */
    public function forInstagramAccount(InstagramAccount $account): static
    {
        return $this->state(fn () => [
            'instagram_account_id' => $account->id,
        ]);
    }
}