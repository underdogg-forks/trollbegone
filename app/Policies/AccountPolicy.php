<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

/**
 * Account Policy
 *
 * Ensures users can only access their own Instagram accounts.
 * Implements multi-tenant security for Instagram account management.
 */
class AccountPolicy
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
    public function view(User $user, Account $account): bool
    {
        // User can only view their own Instagram accounts
        return $user->id === $account->user_id;
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
    public function update(User $user, Account $account): bool
    {
        // User can only update their own Instagram accounts
        return $user->id === $account->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Account $account): bool
    {
        // User can only delete their own Instagram accounts
        return $user->id === $account->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Account $account): bool
    {
        // User can only restore their own Instagram accounts
        return $user->id === $account->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Account $account): bool
    {
        // User can only force delete their own Instagram accounts
        return $user->id === $account->user_id;
    }
}
