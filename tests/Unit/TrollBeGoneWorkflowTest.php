<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\User;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\Fakes\FakeInstagramApiService;
use Tests\Fixtures\InstagramApiFixtures;
use Tests\TestCase;

class TrollBeGoneWorkflowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_api_key_for_a_logged_in_simple_user(): void
    {
        /* Arrange */
        $user = User::factory()->create();

        /* Act */
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'instagram_api_key_123',
        ]);

        /* Assert */
        $this->assertNotNull($account->access_token);
        $this->assertSame('instagram_api_key_123', $account->access_token);
    }

    #[Test]
    public function it_retrieves_posts_from_a_followed_user(): void
    {
        /* Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/search', InstagramApiFixtures::getFollowedUserSearchResponse());
        $fakeHttpClient->addResponse('/followed-1/media', InstagramApiFixtures::getFollowedUserMediaResponse());

        $service = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $account = Account::factory()->create(['access_token' => 'token']);

        /* Act */
        $posts = $service->getPostsByUsername($account, 'followed_user');

        /* Assert */
        $this->assertCount(2, $posts);
        $this->assertSame('post-1', $posts->first()['id']);
    }

    #[Test]
    public function it_retrieves_comments_and_filters_trollbegone_tagged_items(): void
    {
        /* Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/post-1/comments', InstagramApiFixtures::getPostCommentsWithTrollBeGoneTagsResponse());

        $service = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $account = Account::factory()->create(['access_token' => 'token']);

        /* Act */
        $comments = $service->getPostComments($account, 'post-1');
        $tagged = $service->filterCommentsByTag($comments, 'TrollBeGone');
        $withoutTags = $service->filterCommentsWithoutTags($comments);
        $taggedCommentIds = $tagged->pluck('id')->all();

        /* Assert */
        $this->assertCount(6, $comments);
        $this->assertCount(5, $tagged);
        $this->assertContains('c4', $taggedCommentIds);
        $this->assertContains('c5', $taggedCommentIds);
        $this->assertContains('c6', $taggedCommentIds);
        $this->assertCount(1, $withoutTags);
        $this->assertSame('c1', $withoutTags->first()['id']);
    }

    #[Test]
    public function it_deletes_a_single_comment_and_blocks_the_commenter(): void
    {
        /* Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/comment-10', InstagramApiFixtures::getBlockUserSuccessResponse());

        $instagramApi = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));

        $fakeBlockingApi = new FakeInstagramApiService;
        $fakeBlockingApi->setUserInfoResponse('troll_user', ['id' => 'u-10', 'username' => 'troll_user']);
        $fakeBlockingApi->setBlockUserResult('u-10', true);

        $account = Account::factory()->create(['access_token' => 'token']);

        /* Act */
        $deleted = $instagramApi->deleteComment($account, 'comment-10');
        $blocked = (new BlockedAccountService($fakeBlockingApi))
            ->blockAccount($account, 'troll_user', 'Trolling', 'bad comment');

        /* Assert */
        $deleteRequest = collect($fakeHttpClient->getRequestHistory())
            ->first(fn (array $request): bool => str_ends_with($request['url'], '/comment-10'));
        $this->assertTrue($deleted);
        $this->assertNotNull($deleteRequest);
        $this->assertSame('DELETE', $deleteRequest['method']);
        $this->assertSame(['u-10'], $fakeBlockingApi->getBlockUserCalls());
        $this->assertSame('troll_user', $blocked->blocked_username);
    }

    #[Test]
    public function it_bulk_blocks_users_from_comments_tagged_with_trollbegone(): void
    {
        /* Arrange */
        $fakeApi = new FakeInstagramApiService;
        $fakeApi->setUserInfoResponse('beta', ['id' => 'u-2', 'username' => 'beta']);
        $fakeApi->setUserInfoResponse('gamma', ['id' => 'u-3', 'username' => 'gamma']);
        $fakeApi->setBlockUserResult('u-2', true);
        $fakeApi->setBlockUserResult('u-3', true);

        $account = Account::factory()->create(['access_token' => 'token']);
        $service = new BlockedAccountService($fakeApi);

        /* Act */
        $blocked = $service->blockAccountsFromCommentsByTag($account, collect([
            ['id' => 'c1', 'username' => 'alpha', 'text' => 'normal comment'],
            ['id' => 'c2', 'username' => 'beta', 'text' => 'spam #TrollBeGone'],
            ['id' => 'c3', 'username' => 'gamma', 'text' => 'rude #trollbegone'],
        ]), 'TrollBeGone');

        /* Assert */
        $this->assertCount(2, $blocked);
        $this->assertSame(['u-2', 'u-3'], $fakeApi->getBlockUserCalls());
        $this->assertDatabaseHas('blocked_accounts', ['instagram_account_id' => $account->id, 'blocked_username' => 'beta']);
        $this->assertDatabaseHas('blocked_accounts', ['instagram_account_id' => $account->id, 'blocked_username' => 'gamma']);
    }

    #[Test]
    public function it_uses_stored_access_token_when_making_instagram_requests(): void
    {
        /* Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/following', ['data' => []]);
        $service = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $account = Account::factory()->create(['access_token' => 'stored_token_abc']);

        /* Act */
        $service->getFollowing($account);
        $request = collect($fakeHttpClient->getRequestHistory())->first();

        /* Assert */
        $this->assertNotNull($request);
        $this->assertArrayHasKey('token', $request['options']);
        $this->assertSame('stored_token_abc', $request['options']['token']);
    }

    #[Test]
    public function it_uses_the_renewed_access_token_for_subsequent_requests(): void
    {
        /* Arrange */
        $fakeHttpClient = new FakeHttpClient;
        $fakeHttpClient->addResponse('/me/following', ['data' => []]);
        $service = new InstagramApiService(new HttpClientExceptionDecorator($fakeHttpClient));
        $account = Account::factory()->create(['access_token' => 'old_token']);

        /* Act */
        $service->getFollowing($account);
        $account->update(['access_token' => 'renewed_token']);
        $service->getFollowing($account);
        $requestHistory = $fakeHttpClient->getRequestHistory();

        /* Assert */
        $this->assertCount(2, $requestHistory);
        $this->assertArrayHasKey('token', $requestHistory[0]['options']);
        $this->assertArrayHasKey('token', $requestHistory[1]['options']);
        $this->assertSame('old_token', $requestHistory[0]['options']['token']);
        $this->assertSame('renewed_token', $requestHistory[1]['options']['token']);
    }
}
