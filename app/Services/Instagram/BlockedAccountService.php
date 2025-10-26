<?php

namespace App\Services\Instagram;

use App\Models\InstagramAccount;
use App\Models\BlockedAccount;
use Illuminate\Support\Str;

class BlockedAccountService
{
    public function __construct(
        protected InstagramApiService $instagramApi
    ) {}

    public function blockAccount(
        InstagramAccount $account,
        string $username,
        ?string $reason = null,
        ?string $commentText = null
    ): BlockedAccount {
        $userInfo = $this->instagramApi->getUserInfo($account, $username);
        
        $blockedAccount = BlockedAccount::firstOrCreate(
            [
                'instagram_account_id' => $account->id,
                'blocked_username' => strtolower($username),
            ],
            [
                'blocked_instagram_id' => $userInfo['id'] ?? null,
                'reason' => $reason,
                'comment_text' => $commentText,
            ]
        );

        if ($userInfo && isset($userInfo['id'])) {
            $this->instagramApi->blockUser($account, $userInfo['id']);
        }

        return $blockedAccount;
    }

    public function isBlocked(InstagramAccount $account, string $username): bool
    {
        return BlockedAccount::where('instagram_account_id', $account->id)
            ->where('blocked_username', Str::lower($username))
            ->exists();
    }

    public function getBlockedAccounts(InstagramAccount $account): \Illuminate\Database\Eloquent\Collection
    {
        return $account->blockedAccounts()->latest()->get();
    }
}
