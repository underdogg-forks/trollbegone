<?php

namespace App\Services\Instagram;

use App\Models\InstagramAccount;
use App\Models\BlockedAccount;
use Illuminate\Support\Facades\DB;

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
     * @param InstagramAccount $account The Instagram account performing the block
     * @param string $username The username to block
     * @param string|null $reason Optional reason for blocking
     * @param string|null $commentText Optional comment text that triggered the block
     * @return BlockedAccount The created blocked account record
     * @throws \Exception If there's an error during the process
     */
    public function blockAccount(
        InstagramAccount $account,
        string $username,
        ?string $reason = null,
        ?string $commentText = null
    ): BlockedAccount {
        try {
            return DB::transaction(function () use ($account, $username, $reason, $commentText) {
                try {
                    $userInfo = $this->instagramApi->getUserInfo($account, $username);
                } catch (\Exception $e) {
                    // If we can't get user info, continue with null
                    $userInfo = null;
                }

                $blockedAccount = BlockedAccount::create([
                    'instagram_account_id' => $account->id,
                    'blocked_username' => $username,
                    'blocked_instagram_id' => $userInfo['id'] ?? null,
                    'reason' => $reason,
                    'comment_text' => $commentText,
                ]);

                if ($userInfo && isset($userInfo['id'])) {
                    try {
                        $this->instagramApi->blockUser($account, $userInfo['id']);
                    } catch (\Exception $e) {
                        // Block failed on Instagram but we keep the local record
                        // Could log this error
                    }
                }

                return $blockedAccount;
            });
        } catch (\Exception $e) {
            throw new \Exception("Failed to block account: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Check if a username is blocked for a specific Instagram account.
     *
     * @param InstagramAccount $account The Instagram account
     * @param string $username The username to check
     * @return bool True if the username is blocked, false otherwise
     */
    public function isBlocked(InstagramAccount $account, string $username): bool
    {
        return BlockedAccount::where('instagram_account_id', $account->id)
            ->where('blocked_username', $username)
            ->exists();
    }

    /**
     * Get all blocked accounts for a specific Instagram account.
     *
     * @param InstagramAccount $account The Instagram account
     * @return \Illuminate\Database\Eloquent\Collection Collection of blocked accounts
     */
    public function getBlockedAccounts(InstagramAccount $account)
    {
        return $account->blockedAccounts()->latest()->get();
    }
}
