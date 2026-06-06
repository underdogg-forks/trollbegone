<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SocialiteTestHelpers;
use Tests\TestCase;

class InstagramOAuthControllerTest extends TestCase
{
    use RefreshDatabase;
    use SocialiteTestHelpers;

    #[Test]
    public function it_stores_access_token_from_oauth_callback_for_authenticated_user(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $this->actingAs($user);

        $instagramUser = $this->makeSocialiteUser(
            id: 'ig-user-1',
            nickname: 'connected_user',
            name: 'Connected User',
            token: 'oauth_token_123'
        );
        $this->fakeSocialiteDriver($instagramUser);
        /** #endregion */

        /** #region Act */
        $response = $this->get(route('instagram.oauth.callback'));
        /** #endregion */

        /** #region Assert */
        $response->assertRedirect(route('filament.admin.resources.accounts.index'));
        $this->assertDatabaseHas('instagram_accounts', [
            'user_id' => $user->id,
            'instagram_id' => 'ig-user-1',
            'username' => 'connected_user',
            'is_active' => 1,
        ]);
        $this->assertSame(
            'oauth_token_123',
            Account::query()->where('instagram_id', 'ig-user-1')->firstOrFail()->access_token
        );
        /** #endregion */
    }

    #[Test]
    public function it_renews_token_when_same_instagram_account_reconnects(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $this->actingAs($user);

        Account::factory()->create([
            'user_id' => $user->id,
            'instagram_id' => 'ig-user-2',
            'username' => 'reconnect_user',
            'access_token' => 'old_token',
            'is_active' => true,
        ]);

        $instagramUser = $this->makeSocialiteUser(
            id: 'ig-user-2',
            nickname: 'reconnect_user',
            name: 'Reconnect User',
            token: 'renewed_token'
        );
        $this->fakeSocialiteDriver($instagramUser);
        /** #endregion */

        /** #region Act */
        $this->get(route('instagram.oauth.callback'));
        /** #endregion */

        /** #region Assert */
        $account = Account::query()->where('instagram_id', 'ig-user-2')->firstOrFail();
        $this->assertSame('renewed_token', $account->access_token);
        $this->assertSame($user->id, $account->user_id);
        $this->assertTrue($account->is_active);
        /** #endregion */
    }

    #[Test]
    public function it_redirects_unauthenticated_user_to_login_on_callback(): void
    {
        /** #region Arrange */
        $instagramUser = $this->makeSocialiteUser(
            id: 'ig-unauth',
            nickname: 'unauth_user',
            name: 'Unauth User',
            token: 'some_token'
        );
        $this->fakeSocialiteDriver($instagramUser);
        /** #endregion */

        /** #region Act */
        $response = $this->get(route('instagram.oauth.callback'));
        /** #endregion */

        /** #region Assert */
        $response->assertRedirect(route('filament.admin.auth.login'));
        $this->assertDatabaseMissing('instagram_accounts', ['instagram_id' => 'ig-unauth']);
        /** #endregion */
    }
}
