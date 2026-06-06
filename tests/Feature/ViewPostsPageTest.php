<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ViewPosts;
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
 * ViewPosts Page Tests
 *
 * Tests the "For Grandma" admin panel workflow for viewing posts from followed users.
 * Covers the problem statement requirement: "Browse Posts: Click on any user to see their posts"
 */
class ViewPostsPageTest extends TestCase
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
    public function it_displays_posts_for_a_specific_username(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        // First call: search for user
        $fakeHttpClient->addResponse('/search', [
            'data' => [
                [
                    'id' => 'followed_user_id',
                    'username' => 'friend_account',
                ],
            ],
        ]);
        // Second call: get user's posts
        $fakeHttpClient->addResponse('/followed_user_id/media', [
            'data' => [
                [
                    'id' => 'post_1',
                    'caption' => 'Beautiful sunset photo',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://example.com/post1.jpg',
                    'timestamp' => '2024-01-15T10:30:00+0000',
                ],
                [
                    'id' => 'post_2',
                    'caption' => 'Video of the day',
                    'media_type' => 'VIDEO',
                    'media_url' => 'https://example.com/post2.mp4',
                    'timestamp' => '2024-01-14T15:45:00+0000',
                ],
            ],
        ]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewPosts::class, [
            'record' => $this->account,
            'username' => 'friend_account',
        ]);
        /** #endregion */

        /** #region Assert */
        $component->assertSee('Beautiful sunset photo');
        $component->assertSee('Video of the day');
        $this->assertEquals('friend_account', $component->username);
        $this->assertCount(2, $component->posts);
        /** #endregion */
    }

    #[Test]
    public function it_handles_user_not_found_gracefully(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        // User search returns empty
        $fakeHttpClient->addResponse('/search', ['data' => []]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewPosts::class, [
            'record' => $this->account,
            'username' => 'nonexistent_user',
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEmpty($component->posts);
        /** #endregion */
    }

    #[Test]
    public function it_handles_user_with_no_posts(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/search', [
            'data' => [
                ['id' => 'user_id', 'username' => 'empty_account'],
            ],
        ]);
        $fakeHttpClient->addResponse('/user_id/media', ['data' => []]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewPosts::class, [
            'record' => $this->account,
            'username' => 'empty_account',
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEmpty($component->posts);
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
        $component = Livewire::test(ViewPosts::class, [
            'record' => $this->account,
            'username' => 'any_user',
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEmpty($component->posts);
        /** #endregion */
    }

    #[Test]
    public function it_uses_account_access_token_for_api_requests(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/search', [
            'data' => [
                ['id' => 'user_id', 'username' => 'test_user'],
            ],
        ]);
        $fakeHttpClient->addResponse('/user_id/media', ['data' => []]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        Livewire::test(ViewPosts::class, [
            'record' => $this->account,
            'username' => 'test_user',
        ]);
        /** #endregion */

        /** #region Assert */
        $requestHistory = $fakeHttpClient->getRequestHistory();
        $this->assertGreaterThanOrEqual(2, count($requestHistory));

        foreach ($requestHistory as $request) {
            $this->assertSame('test-token', $request['options']['token']);
        }
        /** #endregion */
    }

    #[Test]
    public function it_refreshes_posts_when_refresh_action_is_triggered(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        // Initial load
        $fakeHttpClient->addResponse('/search', [
            'data' => [['id' => 'user_id', 'username' => 'test_user']],
        ]);
        $fakeHttpClient->addResponse('/user_id/media', [
            'data' => [['id' => 'post_1', 'caption' => 'Initial post']],
        ]);
        // Refresh
        $fakeHttpClient->addResponse('/search', [
            'data' => [['id' => 'user_id', 'username' => 'test_user']],
        ]);
        $fakeHttpClient->addResponse('/user_id/media', [
            'data' => [
                ['id' => 'post_1', 'caption' => 'Initial post'],
                ['id' => 'post_2', 'caption' => 'New post after refresh'],
            ],
        ]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewPosts::class, [
            'record' => $this->account,
            'username' => 'test_user',
        ]);
        $component->callAction('refresh');
        /** #endregion */

        /** #region Assert */
        $component->assertSee('New post after refresh');
        $this->assertCount(2, $component->posts);
        /** #endregion */
    }

    #[Test]
    public function it_formats_timestamps_for_display(): void
    {
        /** #region Arrange */
        $component = Livewire::test(ViewPosts::class, [
            'record' => $this->account,
            'username' => 'test_user',
        ]);
        /** #endregion */

        /** #region Act */
        $formattedTime = $component->instance()->formatTimestamp('2024-01-15T10:30:00+0000');
        /** #endregion */

        /** #region Assert */
        $this->assertIsString($formattedTime);
        $this->assertNotEmpty($formattedTime);
        /** #endregion */
    }

    #[Test]
    public function it_provides_back_to_following_action(): void
    {
        /** #region Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/search', ['data' => []]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ViewPosts::class, [
            'record' => $this->account,
            'username' => 'test_user',
        ]);
        /** #endregion */

        /** #region Assert */
        $component->assertSee('Back to Following');
        /** #endregion */
    }
}
