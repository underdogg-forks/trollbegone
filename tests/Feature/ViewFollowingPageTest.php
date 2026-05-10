<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ViewFollowing;
use App\Models\Account;
use App\Models\User;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

/**
 * ViewFollowing Page Tests
 *
 * Tests the "For Grandma" admin panel workflow for viewing Instagram following list.
 * Covers the problem statement requirement: "View Following: Click 'View Following' to see users you follow on Instagram"
 */
class ViewFollowingPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);

        $this->account = Account::factory()->create([
            'user_id' => $this->adminUser->id,
            'username' => 'grandma_account',
            'access_token' => 'test-token',
        ]);
    }

    #[Test]
    public function it_displays_following_list_from_instagram_api(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/following', [
            'data' => [
                [
                    'id' => 'user_1',
                    'username' => 'friend_account',
                    'full_name' => 'Best Friend',
                    'profile_picture_url' => 'https://example.com/friend.jpg',
                    'followers_count' => 1500,
                ],
                [
                    'id' => 'user_2',
                    'username' => 'family_member',
                    'full_name' => 'Family Member',
                    'profile_picture_url' => 'https://example.com/family.jpg',
                    'followers_count' => 500,
                ],
            ],
        ]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewFollowing::class, ['record' => $this->account->id]);
        /** #endregion */

        /** #region Assert */
        $component->assertSee('friend_account');
        $component->assertSee('Best Friend');
        $component->assertSee('family_member');
        $component->assertSee('Family Member');
        /** #endregion */
    }

    #[Test]
    public function it_handles_empty_following_list_gracefully(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/following', ['data' => []]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewFollowing::class, ['record' => $this->account->id]);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(0, $component->getFollowing());
        /** #endregion */
    }

    #[Test]
    public function it_handles_api_failure_gracefully(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->throwExceptionOnNextRequest('API Error');

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewFollowing::class, ['record' => $this->account->id]);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(0, $component->getFollowing());
        /** #endregion */
    }

    #[Test]
    public function it_uses_account_access_token_for_api_request(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/following', ['data' => []]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        Livewire::test(ViewFollowing::class, ['record' => $this->account->id]);
        /** #endregion */

        /** #region Assert */
        $requestHistory = $fakeHttpClient->getRequestHistory();
        $this->assertCount(1, $requestHistory);

        $followingRequest = $requestHistory[0];
        $this->assertEquals('GET', $followingRequest['method']);
        $this->assertStringContainsString('/me/following', $followingRequest['url']);
        $this->assertStringContainsString('access_token=test-token', $followingRequest['url']);
        /** #endregion */
    }

    #[Test]
    public function it_provides_view_posts_action_for_each_following(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/following', [
            'data' => [
                [
                    'id' => 'user_1',
                    'username' => 'friend_account',
                    'full_name' => 'Best Friend',
                ],
            ],
        ]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewFollowing::class, ['record' => $this->account->id]);
        /** #endregion */

        /** #region Assert */
        $component->assertSee('View Posts');
        /** #endregion */
    }

    #[Test]
    public function it_refreshes_following_list_when_refresh_action_is_triggered(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/following', [
            'data' => [
                ['id' => 'user_1', 'username' => 'initial_user'],
            ],
        ]);
        $fakeHttpClient->addResponse('/me/following', [
            'data' => [
                ['id' => 'user_2', 'username' => 'refreshed_user'],
            ],
        ]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewFollowing::class, ['record' => $this->account->id]);
        $component->callAction('refresh');
        /** #endregion */

        /** #region Assert */
        $component->assertSee('refreshed_user');
        /** #endregion */
    }

    #[Test]
    public function it_shows_only_following_for_the_specific_account(): void
    {
        /** #region Arrange */
        $account2 = Account::factory()->create([
            'user_id' => $this->adminUser->id,
            'username' => 'second_account',
            'access_token' => 'second-token',
        ]);

        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/following', [
            'data' => [
                ['id' => 'user_1', 'username' => 'account_1_following'],
            ],
        ]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewFollowing::class, ['record' => $this->account->id]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals($this->account->id, $component->record->id);
        $component->assertSee('account_1_following');
        /** #endregion */
    }
}
