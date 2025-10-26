<?php

namespace Tests\Unit;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\InstagramApiService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\InstagramApiFixtures;
use Tests\TestCase;

/**
 * Integration tests for BlockedAccountService using fixtures.
 * These tests validate the complete blocking workflow with realistic API responses.
 */
class BlockedAccountServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function complete_blocking_workflow_with_fixtures(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $fixtureUser = InstagramApiFixtures::getSingleUser();

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $mockInstagramApi->shouldReceive('getUserInfo')
            ->once()
            ->with($account, 'test_user')
            ->andReturn($fixtureUser);

        $mockInstagramApi->shouldReceive('blockUser')
            ->once()
            ->with($account, $fixtureUser['id'])
            ->andReturn(true);

        $service = new BlockedAccountService($mockInstagramApi);
        /** #endregion */

        /** #region Act */
        $blockedAccount = $service->blockAccount(
            $account,
            'test_user',
            'Spam content',
            'Check out this spam link!'
        );
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('test_user', $blockedAccount->blocked_username);
        $this->assertEquals($fixtureUser['id'], $blockedAccount->blocked_instagram_id);
        $this->assertEquals('Spam content', $blockedAccount->reason);
        $this->assertEquals('Check out this spam link!', $blockedAccount->comment_text);
        /** #endregion */
    }

    #[Test]
    public function blocking_workflow_when_user_search_fails(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $mockInstagramApi->shouldReceive('getUserInfo')
            ->once()
            ->with($account, 'unknown_user')
            ->andReturn(null);

        // Should not call blockUser if getUserInfo returns null
        $mockInstagramApi->shouldNotReceive('blockUser');

        $service = new BlockedAccountService($mockInstagramApi);
        /** #endregion */

        /** #region Act */
        $blockedAccount = $service->blockAccount(
            $account,
            'unknown_user',
            'Suspicious activity'
        );
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('unknown_user', $blockedAccount->blocked_username);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        $this->assertEquals('Suspicious activity', $blockedAccount->reason);
        /** #endregion */
    }

    #[Test]
    public function blocking_multiple_users_from_comments(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $comments = InstagramApiFixtures::getStoryCommentsResponse()['data'];

        // Filter spam comments
        $spamComments = array_filter($comments, function ($comment) {
            return str_contains(strtolower($comment['text']), 'spam');
        });
        /** #endregion */

        /** #region Act */
        foreach ($spamComments as $comment) {
            $mockInstagramApi = Mockery::mock(InstagramApiService::class);
            $mockInstagramApi->shouldReceive('getUserInfo')
                ->once()
                ->andReturn([
                    'id' => $comment['from']['id'],
                    'username' => $comment['from']['username'],
                ]);

            $mockInstagramApi->shouldReceive('blockUser')
                ->once()
                ->andReturn(true);

            $service = new BlockedAccountService($mockInstagramApi);
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
        $this->assertCount(1, $blockedAccounts);
        $this->assertEquals('spam_account', $blockedAccounts->first()->blocked_username);
        /** #endregion */
    }

    #[Test]
    public function is_blocked_with_fixture_data(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
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

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);
        /** #endregion */

        /** #region Act */
        $isBlocked = $service->isBlocked($account, $fixtureUser['username']);
        $isNotBlocked = $service->isBlocked($account, 'not_blocked_user');
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($isBlocked);
        $this->assertFalse($isNotBlocked);
        /** #endregion */
    }

    #[Test]
    public function get_blocked_accounts_returns_latest_first(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
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

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);
        /** #endregion */

        /** #region Act */
        $blockedAccounts = $service->getBlockedAccounts($account);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(3, $blockedAccounts);
        // Latest should be first (index 0 has subMinutes(0) = most recent)
        $this->assertEquals('john_doe_123', $blockedAccounts->first()->blocked_username);
        /** #endregion */
    }

    #[Test]
    public function blocking_workflow_when_api_block_fails(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $fixtureUser = InstagramApiFixtures::getSingleUser();

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $mockInstagramApi->shouldReceive('getUserInfo')
            ->once()
            ->andReturn($fixtureUser);

        // Simulate API block failure
        $mockInstagramApi->shouldReceive('blockUser')
            ->once()
            ->andThrow(new Exception('API Error'));

        $service = new BlockedAccountService($mockInstagramApi);
        /** #endregion */

        /** #region Act */
        // Should still create the local record even if API block fails
        $blockedAccount = $service->blockAccount($account, 'test_user', 'Spam');
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('test_user', $blockedAccount->blocked_username);
        /** #endregion */
    }
}
