<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * InstagramOAuthController handles OAuth authentication flow for Instagram accounts.
 * This allows users to connect their Instagram accounts through the Instagram Graph API.
 */
class InstagramOAuthController extends Controller
{
    /**
     * Redirect the user to the Instagram authentication page.
     */
    public function redirectToProvider(): RedirectResponse
    {
        return Socialite::driver('instagram')
            ->scopes(['instagram_basic', 'instagram_manage_comments', 'instagram_manage_insights'])
            ->redirect();
    }

    /**
     * Obtain the user information from Instagram after authentication.
     */
    public function handleProviderCallback(): RedirectResponse
    {
        try {
            $instagramUser = Socialite::driver('instagram')->user();

            $userId = Auth::id();

            if (! $userId) {
                return redirect()
                    ->route('login')
                    ->with('error', 'Unable to link Instagram account: no authenticated user.');
            }

            // Create or update the Instagram account
            $account = InstagramAccount::updateOrCreate(
                [
                    'instagram_id' => $instagramUser->getId(),
                ],
                [
                    'user_id' => $userId,
                    'username' => $instagramUser->getNickname() ?? $instagramUser->getName(),
                    'access_token' => $instagramUser->token,
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]
            );

            return redirect()
                ->route('filament.admin.resources.instagram-accounts.index')
                ->with('success', "Instagram account @{$account->username} connected successfully!");

        } catch (\Exception $e) {
            return redirect()
                ->route('filament.admin.resources.instagram-accounts.index')
                ->with('error', 'Failed to connect Instagram account: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect an Instagram account by revoking the access token.
     */
    public function disconnect(InstagramAccount $account): RedirectResponse
    {
        try {
            $this->authorize('update', $account);
            // Clear the access token
            $account->update([
                'access_token' => null,
                'is_active' => false,
            ]);

            return redirect()
                ->back()
                ->with('success', "Instagram account @{$account->username} disconnected successfully!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to disconnect Instagram account: ' . $e->getMessage());
        }
    }
}
