<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

/**
 * OAuth Workflow Integration Tests
 *
 * Tests the complete OAuth workflow with token retrieval and usage.
 * Covers the problem statement requirements:
 * - Token storage during OAuth callback
 * - Token renewal when reconnecting
 * - Token usage in Instagram API requests
 * - Verification that renewed tokens are used in subsequent API calls
 */
class OAuthWorkflowIntegrationTest extends TestCase
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
    public function it_stores_token_during_oauth_and_uses_it_for_api_requests(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $this->actingAs($user);

        // OAuth flow - user connects their Instagram account
        $instagramUser = $this->makeSocialiteUser(
            id: 'ig-123',
            nickname: 'connected_user',
            name: 'Connected User',
            token: 'fresh_oauth_token_abc123'
        );
        $this->fakeSocialiteDriver($instagramUser);

        // Setup fake HTTP client to verify token usage
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/stories', ['data' => []]);
        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        // Step 1: OAuth callback stores the token
        $this->get(route('instagram.oauth.callback'));

        // Step 2: Fetch the account from database
        $account = Account::query()->where('instagram_id', 'ig-123')->firstOrFail();

        // Step 3: Use the account to make an API call
        $instagramApi->getStories($account);
        /** #endregion */

        /** #region Assert */
        // Verify token was stored
        $this->assertSame('fresh_oauth_token_abc123', $account->access_token);

        // Verify token was used in API request
        $requestHistory = $fakeHttpClient->getRequestHistory();
        $this->assertCount(1, $requestHistory);
        $this->assertStringContainsString('access_token=fresh_oauth_token_abc123', $requestHistory[0]['url']);
        /** #endregion */
    }

    #[Test]
    public function it_renews_token_and_uses_new_token_for_subsequent_api_calls(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create account with old token
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'instagram_id' => 'ig-456',
            'username' => 'existing_user',
            'access_token' => 'old_token_xyz',
            'is_active' => true,
        ]);

        // Setup fake HTTP client
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/stories', ['data' => []]);
        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);

        // Verify old token works before renewal
        $instagramApi->getStories($account);
        $this->assertStringContainsString('old_token_xyz', $fakeHttpClient->getRequestHistory()[0]['url']);

        // User reconnects - OAuth flow with new token
        $instagramUser = $this->makeSocialiteUser(
            id: 'ig-456',
            nickname: 'existing_user',
            name: 'Existing User',
            token: 'renewed_token_def456'
        );
        $this->fakeSocialiteDriver($instagramUser);

        // Add another API response for the call with renewed token
        $fakeHttpClient->addResponse('/me/stories', ['data' => []]);
        /** #endregion */

        /** #region Act */
        // Step 1: OAuth callback renews the token
        $this->get(route('instagram.oauth.callback'));

        // Step 2: Refresh account from database
        $account->refresh();

        // Step 3: Make another API call with renewed token
        $instagramApi->getStories($account);
        /** #endregion */

        /** #region Assert */
        // Verify token was renewed
        $this->assertSame('renewed_token_def456', $account->access_token);

        // Verify new token was used in subsequent API request
        $requestHistory = $fakeHttpClient->getRequestHistory();
        $this->assertCount(2, $requestHistory);
        $this->assertStringContainsString('renewed_token_def456', $requestHistory[1]['url']);
        /** #endregion */
    }

    #[Test]
    public function it_prevents_unauthenticated_users_from_connecting_instagram(): void
    {
        /** #region Arrange */
        // No user authenticated
        $instagramUser = $this->makeSocialiteUser(
            id: 'ig-789',
            nickname: 'test_user',
            name: 'Test User',
            token: 'some_token'
        );
        $this->fakeSocialiteDriver($instagramUser);
        /** #endregion */

        /** #region Act */
        $response = $this->get(route('instagram.oauth.callback'));
        /** #endregion */

        /** #region Assert */
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('instagram_accounts', [
            'instagram_id' => 'ig-789',
        ]);
        /** #endregion */
    }

    #[Test]
    public function it_associates_token_with_correct_user_in_multi_user_environment(): void
    {
        /** #region Arrange */
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);

        // User 1 connects their Instagram
        $this->actingAs($user1);
        $instagramUser1 = $this->makeSocialiteUser(
            id: 'ig-user1',
            nickname: 'user1_instagram',
            name: 'User 1 Instagram',
            token: 'user1_token'
        );
        $this->fakeSocialiteDriver($instagramUser1);
        $this->get(route('instagram.oauth.callback'));

        // User 2 connects their Instagram
        $this->actingAs($user2);
        $instagramUser2 = $this->makeSocialiteUser(
            id: 'ig-user2',
            nickname: 'user2_instagram',
            name: 'User 2 Instagram',
            token: 'user2_token'
        );
        $this->fakeSocialiteDriver($instagramUser2);
        /** #endregion */

        /** #region Act */
        $this->get(route('instagram.oauth.callback'));
        /** #endregion */

        /** #region Assert */
        $account1 = Account::query()->where('instagram_id', 'ig-user1')->firstOrFail();
        $account2 = Account::query()->where('instagram_id', 'ig-user2')->firstOrFail();

        $this->assertEquals($user1->id, $account1->user_id);
        $this->assertEquals($user2->id, $account2->user_id);
        $this->assertSame('user1_token', $account1->access_token);
        $this->assertSame('user2_token', $account2->access_token);
        /** #endregion */
    }

    #[Test]
    public function it_marks_account_as_active_after_successful_oauth(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $this->actingAs($user);

        $instagramUser = $this->makeSocialiteUser(
            id: 'ig-999',
            nickname: 'active_user',
            name: 'Active User',
            token: 'active_token'
        );
        $this->fakeSocialiteDriver($instagramUser);
        /** #endregion */

        /** #region Act */
        $this->get(route('instagram.oauth.callback'));
        /** #endregion */

        /** #region Assert */
        $account = Account::query()->where('instagram_id', 'ig-999')->firstOrFail();
        $this->assertTrue($account->is_active);
        $this->assertNotNull($account->last_synced_at);
        /** #endregion */
    }

    #[Test]
    public function it_updates_username_when_reconnecting_with_changed_username(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Initial connection
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'instagram_id' => 'ig-111',
            'username' => 'old_username',
            'access_token' => 'old_token',
        ]);

        // User changed their Instagram username and reconnects
        $instagramUser = $this->makeSocialiteUser(
            id: 'ig-111',
            nickname: 'new_username',
            name: 'User',
            token: 'new_token'
        );
        $this->fakeSocialiteDriver($instagramUser);
        /** #endregion */

        /** #region Act */
        $this->get(route('instagram.oauth.callback'));
        /** #endregion */

        /** #region Assert */
        $account->refresh();
        $this->assertEquals('new_username', $account->username);
        $this->assertSame('new_token', $account->access_token);
        /** #endregion */
    }
}
