<?php

namespace Tests\Unit;

use App\Jobs\BlockUserJob;
use App\Models\Account;
use App\Models\User;
use App\Services\Instagram\CommentModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\InstagramApiFixtures;
use Tests\TestCase;

class CommentModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_groups_first_comment_per_selected_username(): void
    {
        /** #region Arrange */
        $service = new CommentModerationService;
        $comments = collect(InstagramApiFixtures::getCommenterModerationComments());
        /** #endregion */

        /** #region Act */
        $grouped = $service->getCommentsGroupedBySelectedUsernames($comments, ['alpha', 'beta']);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(2, $grouped);
        $this->assertSame('first', $grouped->get('alpha')['text']);
        $this->assertSame('third', $grouped->get('beta')['text']);
        /** #endregion */
    }

    #[Test]
    public function it_builds_unique_commenters_summary_for_display(): void
    {
        /** #region Arrange */
        $service = new CommentModerationService;
        $comments = collect(InstagramApiFixtures::getCommenterModerationComments());
        /** #endregion */

        /** #region Act */
        $summary = $service->getUniqueCommenters($comments);
        /** #endregion */

        /** #region Assert */
        $alpha = $summary->firstWhere('username', 'alpha');
        $beta = $summary->firstWhere('username', 'beta');

        $this->assertSame(2, $alpha['comment_count']);
        $this->assertSame('second', $alpha['latest_comment']);
        $this->assertSame(1, $beta['comment_count']);
        $this->assertSame('third', $beta['latest_comment']);
        /** #endregion */
    }

    #[Test]
    public function it_dispatches_block_jobs_for_grouped_commenters(): void
    {
        /** #region Arrange */
        Queue::fake();
        $service = new CommentModerationService;
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $grouped = collect([
            'alpha' => ['id' => 'c1', 'text' => 'first'],
            'beta' => ['id' => 'c3', 'text' => 'third'],
        ]);
        /** #endregion */

        /** #region Act */
        $service->dispatchBlockJobsForGroupedComments($account, $grouped);
        /** #endregion */

        /** #region Assert */
        Queue::assertPushed(BlockUserJob::class, 2);
        Queue::assertPushed(BlockUserJob::class, function (BlockUserJob $job) use ($account): bool {
            return $job->account->is($account)
                && in_array($job->username, ['alpha', 'beta'], true)
                && $job->reason === 'Blocked from commenters list';
        });
        /** #endregion */
    }
}
