<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstagramOAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSocialiteDriver(SocialiteUserContract $instagramUser): void
    {
        $provider = new class($instagramUser)
        {
            public function __construct(private readonly SocialiteUserContract $instagramUser) {}

            public function user(): SocialiteUserContract
            {
                return $this->instagramUser;
            }
        };

        Socialite::shouldReceive('driver')
            ->once()
            ->with('instagram')
            ->andReturn($provider);
    }

    private function makeSocialiteUser(
        string $id,
        string $nickname,
        string $name,
        string $token
    ): SocialiteUserContract {
        return new class($id, $nickname, $name, $token) implements SocialiteUserContract
        {
            public function __construct(
                private readonly string $id,
                private readonly string $nickname,
                private readonly string $name,
                public string $token
            ) {}

            public function getId()
            {
                return $this->id;
            }

            public function getNickname()
            {
                return $this->nickname;
            }

            public function getName()
            {
                return $this->name;
            }

            public function getEmail()
            {
                return null;
            }

            public function getAvatar()
            {
                return null;
            }
        };
    }

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
}
