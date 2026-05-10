<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ViewComments;
use App\Jobs\BlockUserJob;
use App\Models\Account;
use App\Models\User;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeInstagramApiService;
use Tests\TestCase;

class ViewCommentsPageTest extends TestCase
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
    public function it_blocks_a_single_selected_user_via_comments_page_action(): void
    {
        /** #region Arrange */
        Queue::fake();
        $fakeApi = new FakeInstagramApiService;
        $fakeApi->setPostCommentsResponse('post-1', collect([
            ['id' => 'comment-1', 'username' => 'single_troll', 'text' => 'bye'],
            ['id' => 'comment-2', 'username' => 'good_user', 'text' => 'hello'],
        ]));
        $this->app->instance(InstagramApiService::class, $fakeApi);
        /** #endregion */

        /** #region Act */
        Livewire::test(ViewComments::class, ['record' => $this->account, 'post' => 'post-1'])
            ->assertSee('single_troll')
            ->call('toggleComment', 'comment-1')
            ->assertSet('selectedComments', ['comment-1'])
            ->callAction('block_selected');
        /** #endregion */

        /** #region Assert */
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job): bool {
            return $job->account->is($this->account)
                && $job->username === 'single_troll'
                && $job->reason === 'Blocked from post comments'
                && $job->commentText === 'bye';
        });
        Queue::assertPushed(BlockUserJob::class, 1);
        /** #endregion */
    }

    #[Test]
    public function it_blocks_multiple_selected_users_via_comments_page_button(): void
    {
        /** #region Arrange */
        Queue::fake();
        $fakeApi = new FakeInstagramApiService;
        $fakeApi->setPostCommentsResponse('post-2', collect([
            ['id' => 'comment-10', 'username' => 'alpha', 'text' => 'spam one'],
            ['id' => 'comment-11', 'username' => 'beta', 'text' => 'spam two'],
            ['id' => 'comment-12', 'username' => 'gamma', 'text' => 'normal'],
        ]));
        $this->app->instance(InstagramApiService::class, $fakeApi);
        /** #endregion */

        /** #region Act */
        Livewire::test(ViewComments::class, ['record' => $this->account, 'post' => 'post-2'])
            ->assertSee('alpha')
            ->call('toggleComment', 'comment-10')
            ->call('toggleComment', 'comment-11')
            ->assertSet('selectedComments', ['comment-10', 'comment-11'])
            ->callAction('block_selected')
            ->assertSet('selectedComments', []);
        /** #endregion */

        /** #region Assert */
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job): bool {
            return $job->account->is($this->account)
                && $job->username === 'alpha'
                && $job->reason === 'Blocked from post comments'
                && $job->commentText === 'spam one';
        });
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job): bool {
            return $job->account->is($this->account)
                && $job->username === 'beta'
                && $job->reason === 'Blocked from post comments'
                && $job->commentText === 'spam two';
        });
        Queue::assertPushed(BlockUserJob::class, 2);
        /** #endregion */
    }
}
