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

    #[Test]
    public function it_stores_access_token_from_oauth_callback_for_authenticated_user(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $this->actingAs($user);

        $instagramUser = new class implements SocialiteUserContract
        {
            public string $token = 'oauth_token_123';

            public function getId()
            {
                return 'ig-user-1';
            }

            public function getNickname()
            {
                return 'connected_user';
            }

            public function getName()
            {
                return 'Connected User';
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

        $instagramUser = new class implements SocialiteUserContract
        {
            public string $token = 'renewed_token';

            public function getId()
            {
                return 'ig-user-2';
            }

            public function getNickname()
            {
                return 'reconnect_user';
            }

            public function getName()
            {
                return 'Reconnect User';
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
