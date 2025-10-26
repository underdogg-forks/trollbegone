<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class InstagramOAuthController extends Controller
{
    public function handleProviderCallback(Request $request): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver('instagram')->user();
            $user = Auth::user();

            if (! $user) {
                return redirect()
                    ->route('login')
                    ->with('error', 'Failed to connect Instagram account.');
            }

            $username = $socialiteUser->getNickname() ?: $socialiteUser->getName() ?: 'instagram-user-' . $socialiteUser->getId();

            $account = InstagramAccount::updateOrCreate(
                ['instagram_id' => $socialiteUser->getId()],
                [
                    'username' => $username,
                    'access_token' => $socialiteUser->token,
                    'is_active' => true,
                    'user_id' => $user->id,
                ]
            );

            return redirect()
                ->route('filament.admin.resources.instagram-accounts.index')
                ->with('success', "Successfully connected Instagram account for {$account->username}.");
        } catch (\Exception $e) {
            Log::error('Instagram OAuth callback failed', ['error' => $e->getMessage()]);

            return redirect()
                ->route('filament.admin.resources.instagram-accounts.index')
                ->with('error', 'Failed to connect Instagram account. Please try again.');
        }
    }

    public function disconnect(Request $request, InstagramAccount $account): RedirectResponse
    {
        $this->authorize('update', $account);

        try {
            $account->forceFill([
                'access_token' => null,
                'is_active' => false,
                'last_synced_at' => null,
            ])->save();

            return redirect()
                ->back()
                ->with('success', 'Instagram account disconnected successfully.');
        } catch (\Exception $e) {
            Log::error('Instagram disconnect failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to disconnect Instagram account. Please try again.');
        }
    }
}
