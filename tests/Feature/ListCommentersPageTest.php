<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ListCommenters;
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

class ListCommentersPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_blocks_selected_commenters_from_the_commenters_page(): void
    {
        /** #region Arrange */
        Queue::fake();
        $adminUser = User::factory()->create();
        $this->actingAs($adminUser);

        $account = Account::factory()->create([
            'user_id' => $adminUser->id,
            'access_token' => 'test-token',
        ]);

        $fakeApi = new FakeInstagramApiService;
        $fakeApi->setPostCommentsResponse('post-44', collect([
            ['id' => 'c1', 'username' => 'alpha', 'text' => 'first'],
            ['id' => 'c2', 'username' => 'alpha', 'text' => 'second'],
            ['id' => 'c3', 'username' => 'beta', 'text' => 'third'],
        ]));
        $this->app->instance(InstagramApiService::class, $fakeApi);
        /** #endregion */

        /** #region Act */
        Livewire::test(ListCommenters::class, ['record' => $account, 'post' => 'post-44'])
            ->assertSee('alpha')
            ->assertSee('beta')
            ->call('toggleCommenter', 'alpha')
            ->call('toggleCommenter', 'beta')
            ->call('blockSelectedCommenters');
        /** #endregion */

        /** #region Assert */
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job) use ($account): bool {
            return $job->account->is($account)
                && in_array($job->username, ['alpha', 'beta'], true)
                && $job->reason === 'Blocked from commenters list';
        });
        Queue::assertPushed(BlockUserJob::class, 2);
        /** #endregion */
    }
}
