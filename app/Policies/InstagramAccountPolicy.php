<?php

namespace App\Policies;

use App\Models\InstagramAccount;
use App\Models\User;

/**
 * InstagramAccount Policy
 *
 * Ensures users can only access their own Instagram accounts.
 * Implements multi-tenant security for Instagram account management.
 */
class InstagramAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view their own accounts (scoped in resource)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InstagramAccount $instagramAccount): bool
    {
        // User can only view their own Instagram accounts
        return $user->id === $instagramAccount->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create Instagram accounts
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InstagramAccount $instagramAccount): bool
    {
        // User can only update their own Instagram accounts
        return $user->id === $instagramAccount->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InstagramAccount $instagramAccount): bool
    {
        // User can only delete their own Instagram accounts
        return $user->id === $instagramAccount->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InstagramAccount $instagramAccount): bool
    {
        // User can only restore their own Instagram accounts
        return $user->id === $instagramAccount->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InstagramAccount $instagramAccount): bool
    {
        // User can only force delete their own Instagram accounts
        return $user->id === $instagramAccount->user_id;
    }
}
