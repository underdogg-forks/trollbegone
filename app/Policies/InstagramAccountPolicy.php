<?php

namespace App\Policies;

use App\Models\InstagramAccount;
use App\Models\User;

class InstagramAccountPolicy
{
    public function update(User $user, InstagramAccount $account): bool
    {
        return $account->user_id !== null && $account->user_id === $user->id;
    }
}
