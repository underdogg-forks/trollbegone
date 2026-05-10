<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\BlockedAccount;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeInstagramApiService;
use Tests\Fixtures\InstagramApiFixtures;
use Tests\TestCase;

/**
 * Integration tests for BlockedAccountService using fixtures.
 * These tests validate the complete blocking workflow with realistic API responses.
 */
class BlockedAccountServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_completes_blocking_workflow_with_fixtures(): void
    {
        /** #region Arrange */
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $fixtureUser = InstagramApiFixtures::getSingleUser();

        $fakeInstagramApi = new FakeInstagramApiService;
        $fakeInstagramApi->setUserInfoResponse('test_user', $fixtureUser);
        $fakeInstagramApi->setBlockUserResult($fixtureUser['id'], true);

        $service = new BlockedAccountService($fakeInstagramApi);

        /** #endregion */

        /** #region Act */
        /* Act */
        $blockedAccount = $service->blockAccount(
            $account,
            'test_user',
            'Spam content',
            'Check out this spam link!'
        );

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('test_user', $blockedAccount->blocked_username);
        $this->assertEquals($fixtureUser['id'], $blockedAccount->blocked_instagram_id);
        $this->assertEquals('Spam content', $blockedAccount->reason);
        $this->assertEquals('Check out this spam link!', $blockedAccount->comment_text);

        /** #endregion */
    }

    #[Test]
    public function it_handles_blocking_workflow_when_user_search_fails(): void
    {
        /** #region Arrange */
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $fakeInstagramApi = new FakeInstagramApiService;
        $fakeInstagramApi->setUserInfoResponse('unknown_user', null);

        $service = new BlockedAccountService($fakeInstagramApi);

        /** #endregion */

        /** #region Act */
        /* Act */
        $blockedAccount = $service->blockAccount(
            $account,
            'unknown_user',
            'Suspicious activity'
        );

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('unknown_user', $blockedAccount->blocked_username);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        $this->assertEquals('Suspicious activity', $blockedAccount->reason);

        /** #endregion */
    }

    #[Test]
    public function it_blocks_multiple_users_from_comments(): void
    {
        /** #region Arrange */
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $comments = InstagramApiFixtures::getStoryCommentsResponse()['data'];

        $spamComments = array_filter($comments, function ($comment) {
            return str_contains(strtolower($comment['text']), 'spam');
        });

        /** #endregion */

        /** #region Act */
        /* Act */
        foreach ($spamComments as $comment) {
            $fakeInstagramApi = new FakeInstagramApiService;
            $fakeInstagramApi->setUserInfoResponse(
                $comment['from']['username'],
                [
                    'id' => $comment['from']['id'],
                    'username' => $comment['from']['username'],
                ]
            );
            $fakeInstagramApi->setBlockUserResult($comment['from']['id'], true);

            $service = new BlockedAccountService($fakeInstagramApi);
            $service->blockAccount(
                $account,
                $comment['from']['username'],
                'Spam detected',
                $comment['text']
            );
        }

        $blockedAccounts = BlockedAccount::where('instagram_account_id', $account->id)->get();

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertCount(1, $blockedAccounts);
        $this->assertEquals('spam_account', $blockedAccounts->first()->blocked_username);

        /** #endregion */
    }

    #[Test]
    public function it_checks_if_user_is_blocked_with_fixture_data(): void
    {
        /** #region Arrange */
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $fixtureUser = InstagramApiFixtures::getSingleUser();

        BlockedAccount::create([
            'instagram_account_id' => $account->id,
            'blocked_username' => $fixtureUser['username'],
            'blocked_instagram_id' => $fixtureUser['id'],
            'reason' => 'Spam',
        ]);

        $fakeInstagramApi = new FakeInstagramApiService;
        $service = new BlockedAccountService($fakeInstagramApi);

        /** #endregion */

        /** #region Act */
        /* Act */
        $isBlocked = $service->isBlocked($account, $fixtureUser['username']);
        $isNotBlocked = $service->isBlocked($account, 'not_blocked_user');

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertTrue($isBlocked);
        $this->assertFalse($isNotBlocked);

        /** #endregion */
    }

    #[Test]
    public function it_returns_blocked_accounts_latest_first(): void
    {
        /** #region Arrange */
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $comments = InstagramApiFixtures::getStoryCommentsResponse()['data'];

        foreach ($comments as $index => $comment) {
            BlockedAccount::create([
                'instagram_account_id' => $account->id,
                'blocked_username' => $comment['from']['username'],
                'blocked_instagram_id' => $comment['from']['id'],
                'reason' => 'Test blocking',
                'created_at' => now()->subMinutes($index),
            ]);
        }

        $fakeInstagramApi = new FakeInstagramApiService;
        $service = new BlockedAccountService($fakeInstagramApi);

        /** #endregion */

        /** #region Act */
        /* Act */
        $blockedAccounts = $service->getBlockedAccounts($account);

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertCount(3, $blockedAccounts);
        $usernames = $blockedAccounts->pluck('blocked_username')->toArray();
        $this->assertContains('john_doe_123', $usernames);
        $this->assertContains('jane_smith_456', $usernames);
        $this->assertContains('spam_account', $usernames);

        /** #endregion */
    }

    #[Test]
    public function it_handles_blocking_workflow_when_api_block_fails(): void
    {
        /** #region Arrange */
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $fixtureUser = InstagramApiFixtures::getSingleUser();

        $fakeInstagramApi = new FakeInstagramApiService;
        $fakeInstagramApi->setUserInfoResponse('test_user', $fixtureUser);
        $fakeInstagramApi->setBlockUserResult($fixtureUser['id'], false); // Simulate API failure

        $service = new BlockedAccountService($fakeInstagramApi);

        /** #endregion */

        /** #region Act */
        /* Act */
        $blockedAccount = $service->blockAccount($account, 'test_user', 'Spam');

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('test_user', $blockedAccount->blocked_username);

        /** #endregion */
    }
}
