<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ListComments;
use App\Filament\Resources\Accounts\Pages\ViewFollowing;
use App\Filament\Resources\Accounts\Pages\ViewPosts;
use App\Jobs\BlockUserJob;
use App\Models\Account;
use App\Models\User;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

/**
 * End-to-End Grandma Workflow Test
 *
 * Tests the complete "For Grandma" user journey from the problem statement:
 * 1. Login to TrollBeGone
 * 2. Connect Instagram account via OAuth
 * 3. View connected Instagram accounts
 * 4. View following list
 * 5. Browse posts from a followed user
 * 6. Review comments on a post
 * 7. Block users from comments
 *
 * This test verifies the entire workflow works end-to-end with token management.
 */
class GrandmaWorkflowTest extends TestCase
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
    public function it_completes_full_grandma_workflow_from_login_to_blocking_troll(): void
    {
        /** #region Arrange */
        Queue::fake();

        // Step 1: Grandma logs in
        $grandma = User::factory()->create([
            'name' => 'Grandma',
            'email' => 'grandma@example.com',
        ]);
        $this->actingAs($grandma);

        // Step 2: Grandma connects her Instagram account via OAuth
        $instagramUser = $this->makeSocialiteUser(
            id: 'grandma-instagram-id',
            nickname: 'grandma_account',
            name: 'Grandma Instagram',
            token: 'grandma_access_token'
        );
        $this->fakeSocialiteDriver($instagramUser);
        $this->get(route('instagram.oauth.callback'));

        // Get the connected account
        $account = Account::query()->where('instagram_id', 'grandma-instagram-id')->firstOrFail();

        // Setup Instagram API fake responses for the workflow
        $fakeHttpClient = new FakeHttpClient;

        // Step 3: View following list
        $fakeHttpClient->addResponse('/me/following', [
            'data' => [
                [
                    'id' => 'friend-id',
                    'username' => 'best_friend',
                    'full_name' => 'Best Friend',
                ],
            ],
        ]);

        // Step 4: View posts from followed user
        $fakeHttpClient->addResponse('/search', [
            'data' => [
                ['id' => 'friend-id', 'username' => 'best_friend'],
            ],
        ]);
        $fakeHttpClient->addResponse('/friend-id/media', [
            'data' => [
                [
                    'id' => 'post-123',
                    'caption' => 'Look at my cute cat!',
                ],
            ],
        ]);

        // Step 5: View comments on post
        $fakeHttpClient->addResponse('/post-123/comments', [
            'data' => [
                [
                    'id' => 'comment-1',
                    'username' => 'nice_user',
                    'text' => 'So cute!',
                ],
                [
                    'id' => 'comment-2',
                    'username' => 'troll_user',
                    'text' => 'Spam spam spam!',
                ],
            ],
        ]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        // Step 3: Grandma views her following list
        $followingComponent = Livewire::test(ViewFollowing::class, ['record' => $account->id]);

        // Step 4: Grandma browses posts from a friend
        $postsComponent = Livewire::test(ViewPosts::class, [
            'record' => $account,
            'username' => 'best_friend',
        ]);

        // Step 5: Grandma reviews comments on a post
        $commentsComponent = Livewire::test(ListComments::class, [
            'record' => $account,
            'post' => 'post-123',
        ]);

        // Step 6: Grandma blocks the troll user
        $commentsComponent->call('toggleComment', 'comment-2');
        $commentsComponent->callAction('block_selected');
        /** #endregion */

        /** #region Assert */
        // Verify following list was displayed
        $followingComponent->assertSee('best_friend');

        // Verify posts were displayed
        $postsComponent->assertSee('Look at my cute cat!');

        // Verify comments were displayed
        $commentsComponent->assertSee('nice_user');
        $commentsComponent->assertSee('troll_user');

        // Verify block job was queued for the troll
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job) use ($account) {
            return $job->account->is($account)
                && $job->username === 'troll_user'
                && $job->reason === 'Blocked from post comments';
        });

        // Verify the account's token was used throughout the workflow
        $requestHistory = $fakeHttpClient->getRequestHistory();
        foreach ($requestHistory as $request) {
            $this->assertStringContainsString('access_token=grandma_access_token', $request['url']);
        }
        /** #endregion */
    }

    #[Test]
    public function it_isolates_workflows_for_multiple_concurrent_users(): void
    {
        /** #region Arrange */
        Queue::fake();

        // Two grandmas log in and connect their Instagram accounts
        $grandma1 = User::factory()->create(['name' => 'Grandma 1']);
        $grandma2 = User::factory()->create(['name' => 'Grandma 2']);

        // Grandma 1 connects
        $this->actingAs($grandma1);
        $instagram1 = $this->makeSocialiteUser(
            id: 'grandma1-id',
            nickname: 'grandma1_account',
            name: 'Grandma 1',
            token: 'token_grandma1'
        );
        $this->fakeSocialiteDriver($instagram1);
        $this->get(route('instagram.oauth.callback'));
        $account1 = Account::query()->where('instagram_id', 'grandma1-id')->firstOrFail();

        // Grandma 2 connects
        $this->actingAs($grandma2);
        $instagram2 = $this->makeSocialiteUser(
            id: 'grandma2-id',
            nickname: 'grandma2_account',
            name: 'Grandma 2',
            token: 'token_grandma2'
        );
        $this->fakeSocialiteDriver($instagram2);
        $this->get(route('instagram.oauth.callback'));
        $account2 = Account::query()->where('instagram_id', 'grandma2-id')->firstOrFail();

        // Setup fake responses for both accounts
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/post-a/comments', [
            'data' => [
                ['id' => 'comment-a', 'username' => 'troll_x', 'text' => 'spam'],
            ],
        ]);
        $fakeHttpClient->addResponse('/post-b/comments', [
            'data' => [
                ['id' => 'comment-b', 'username' => 'troll_y', 'text' => 'spam'],
            ],
        ]);

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        // Grandma 1 blocks a troll
        $this->actingAs($grandma1);
        $comments1 = Livewire::test(ListComments::class, [
            'record' => $account1,
            'post' => 'post-a',
        ]);
        $comments1->call('toggleComment', 'comment-a');
        $comments1->callAction('block_selected');

        // Grandma 2 blocks a different troll
        $this->actingAs($grandma2);
        $comments2 = Livewire::test(ListComments::class, [
            'record' => $account2,
            'post' => 'post-b',
        ]);
        $comments2->call('toggleComment', 'comment-b');
        $comments2->callAction('block_selected');
        /** #endregion */

        /** #region Assert */
        // Verify both blocking operations are isolated
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job) use ($account1) {
            return $job->account->is($account1) && $job->username === 'troll_x';
        });

        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job) use ($account2) {
            return $job->account->is($account2) && $job->username === 'troll_y';
        });

        // Verify correct tokens were used for each user
        $requestHistory = $fakeHttpClient->getRequestHistory();
        $this->assertCount(2, $requestHistory);

        // Each request should use the correct account's token
        $tokensUsed = collect($requestHistory)
            ->map(fn ($req) => str_contains($req['url'], 'token_grandma1') ? 'grandma1' : 'grandma2')
            ->all();
        $this->assertContains('grandma1', $tokensUsed);
        $this->assertContains('grandma2', $tokensUsed);
        /** #endregion */
    }

    #[Test]
    public function it_handles_workflow_with_invalid_token_gracefully(): void
    {
        /** #region Arrange */
        $grandma = User::factory()->create(['name' => 'Grandma']);
        $this->actingAs($grandma);

        // Account with invalid/expired token
        $account = Account::factory()->create([
            'user_id' => $grandma->id,
            'username' => 'grandma_account',
            'access_token' => 'expired_token',
        ]);

        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->throwExceptionOnNextRequest('Invalid OAuth token');

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        $followingComponent = Livewire::test(ViewFollowing::class, ['record' => $account->id]);
        /** #endregion */

        /** #region Assert */
        // Workflow should handle error gracefully and show empty list
        $this->assertCount(0, $followingComponent->getFollowing());
        /** #endregion */
    }
}
