<?php

namespace App\Services\Instagram;

use App\Models\Account;
use App\Models\BlockedAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * BlockedAccountService manages blocking functionality for Instagram accounts.
 * This service coordinates between the local database and the Instagram API.
 */
class BlockedAccountService
{
    public function __construct(
        protected InstagramApiService $instagramApi
    ) {}

    /**
     * Block a user on Instagram and record it in the database.
     *
     * This method:
     * 1. Searches for the user on Instagram to get their user ID
     * 2. Creates a local database record
     * 3. Calls the Instagram API to block the user
     *
     * @param  Account  $account  The Instagram account performing the block
     * @param  string  $username  The username to block
     * @param  string|null  $reason  Optional reason for blocking
     * @param  string|null  $commentText  Optional comment text that triggered the block
     * @return BlockedAccount The created blocked account record
     *
     * @throws \Exception If there's an error during the process
     */
    public function blockAccount(
        Account $account,
        string $username,
        ?string $reason = null,
        ?string $commentText = null
    ): BlockedAccount {
        // Fetch user info - don't fail if unavailable
        try {
            $userInfo = $this->instagramApi->getUserInfo($account, $username);
        } catch (\Throwable $e) {
            $userInfo = null;
        }

        // Create database record first - ensures audit trail even if API fails
        $blockedAccount = DB::transaction(function () use ($account, $username, $reason, $commentText, $userInfo) {
            return BlockedAccount::firstOrCreate(
                [
                    'instagram_account_id' => $account->id,
                    'blocked_username' => $username,
                ],
                [
                    'blocked_instagram_id' => $userInfo['id'] ?? null,
                    'reason' => $reason,
                    'comment_text' => $commentText,
                ]
            );
        });

        // Try to block on Instagram - log warning if it fails but don't throw
        if ($userInfo && isset($userInfo['id'])) {
            try {
                $this->instagramApi->blockUser($account, $userInfo['id']);
            } catch (\Throwable $e) {
                logger()->warning('Instagram block failed', [
                    'account_id' => $account->id,
                    'username' => $username,
                    'instagram_user_id' => $userInfo['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $blockedAccount;
    }

    /**
     * Check if a username is blocked for a specific Instagram account.
     *
     * @param  Account  $account  The Instagram account
     * @param  string  $username  The username to check
     * @return bool True if the username is blocked, false otherwise
     */
    public function isBlocked(Account $account, string $username): bool
    {
        return BlockedAccount::where('instagram_account_id', $account->id)
            ->where('blocked_username', $username)
            ->exists();
    }

    /**
     * Get all blocked accounts for a specific Instagram account.
     *
     * @param  Account  $account  The Instagram account
     * @return \Illuminate\Database\Eloquent\Collection Collection of blocked accounts
     */
    public function getBlockedAccounts(Account $account): \Illuminate\Database\Eloquent\Collection
    {
        return $account->blockedAccounts()->latest()->get();
    }

    /**
     * Block users from comments that include a moderation hash tag.
     *
     * Example input comments:
     * [
     *   {"username": "alpha", "text": "normal comment"},
     *   {"username": "beta", "text": "spam #TrollBeGone"}
     * ]
     *
     * Example output:
     * [
     *   {"blocked_username": "beta", "reason": "Blocked from tagged comment"}
     * ]
     */
    public function blockAccountsFromCommentsByTag(
        Account $account,
        Collection $comments,
        string $tag = 'TrollBeGone'
    ): Collection {
        $needle = '#'.ltrim(strtolower($tag), '#');

        return $comments
            ->filter(function (array $comment) use ($needle) {
                $text = strtolower((string) ($comment['text'] ?? ''));

                return isset($comment['username']) && str_contains($text, $needle);
            })
            ->map(fn (array $comment) => $this->blockAccount(
                $account,
                $comment['username'],
                'Blocked from tagged comment',
                $comment['text'] ?? null
            ));
    }
}
