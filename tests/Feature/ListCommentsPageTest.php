<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ListComments;
use App\Jobs\BlockUserJob;
use App\Models\Account;
use App\Models\User;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class ListCommentsPageTest extends TestCase
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
            'access_token' => 'test-token',
        ]);
    }

    #[Test]
    public function it_deletes_a_single_comment_and_queues_a_block_from_list_comments(): void
    {
        /** #region Arrange */
        Queue::fake();
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/post-1/comments', [
            'data' => [
                ['id' => 'comment-1', 'username' => 'single_troll', 'text' => 'bye'],
            ],
        ]);
        $fakeHttpClient->addResponse('/comment-1', ['success' => true]);
        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        Livewire::test(ListComments::class, ['record' => $this->account, 'post' => 'post-1'])
            ->assertSee('single_troll')
            ->call('deleteAndBlockComment', 'comment-1');
        /** #endregion */

        /** #region Assert */
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job): bool {
            return $job->account->is($this->account)
                && $job->username === 'single_troll'
                && $job->reason === 'Deleted and blocked from post comments';
        });

        $deleteRequest = collect($fakeHttpClient->getRequestHistory())
            ->first(fn (array $request): bool => $request['method'] === 'DELETE' && str_ends_with($request['url'], '/comment-1'));
        $this->assertNotNull($deleteRequest);
        /** #endregion */
    }

    #[Test]
    public function it_bulk_deletes_tagged_comments_and_queues_blocks_from_list_comments(): void
    {
        /** #region Arrange */
        Queue::fake();
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/post-2/comments', [
            'data' => [
                ['id' => 'comment-10', 'username' => 'alpha', 'text' => 'spam #TrollBeGone'],
                ['id' => 'comment-11', 'username' => 'beta', 'text' => 'spam #trollbegone'],
                ['id' => 'comment-12', 'username' => 'gamma', 'text' => 'normal comment'],
            ],
        ]);
        $fakeHttpClient->addResponse('/comment-10', ['success' => true]);
        $fakeHttpClient->addResponse('/comment-11', ['success' => true]);
        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $this->app->instance(InstagramApiService::class, $instagramApi);
        /** #endregion */

        /** #region Act */
        Livewire::test(ListComments::class, ['record' => $this->account, 'post' => 'post-2'])
            ->assertSee('alpha')
            ->call('bulkDeleteTaggedComments', 'TrollBeGone');
        /** #endregion */

        /** #region Assert */
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job): bool {
            return in_array($job->username, ['alpha', 'beta'], true)
                && $job->reason === 'Bulk deleted and blocked from tagged comments';
        });
        Queue::assertPushed(BlockUserJob::class, 2);

        $deleteRequests = collect($fakeHttpClient->getRequestHistory())
            ->filter(fn (array $request): bool => $request['method'] === 'DELETE')
            ->values();
        $this->assertCount(2, $deleteRequests);
        /** #endregion */
    }
}
