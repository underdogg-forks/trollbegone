<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\InstagramAccount;
use App\Models\BlockedAccount;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;
use Mockery;
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
    public function it_complete_blocking_workflow_with_fixtures(): void
    {
        $this->markTestIncomplete();
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
        $blockedAccount = $service->blockAccount(
            $account,
            'test_user',
            'Spam content',
            'Check out this spam link!'
        );

        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('test_user', $blockedAccount->blocked_username);
        $this->assertEquals($fixtureUser['id'], $blockedAccount->blocked_instagram_id);
        $this->assertEquals('Spam content', $blockedAccount->reason);
        $this->assertEquals('Check out this spam link!', $blockedAccount->comment_text);
    }

    #[Test]
    public function it_blocking_workflow_when_user_search_fails(): void
    {
        $this->markTestIncomplete();
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
        $blockedAccount = $service->blockAccount(
            $account,
            'unknown_user',
            'Suspicious activity'
        );

        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('unknown_user', $blockedAccount->blocked_username);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        $this->assertEquals('Suspicious activity', $blockedAccount->reason);
    }

    #[Test]
    public function it_blocking_multiple_users_from_comments(): void
    {
        $this->markTestIncomplete();
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
        $this->assertCount(1, $blockedAccounts);
        $this->assertEquals('spam_account', $blockedAccounts->first()->blocked_username);
    }

    #[Test]
    public function it_is_blocked_with_fixture_data(): void
    {
        $this->markTestIncomplete();
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

        $this->assertTrue($service->isBlocked($account, $fixtureUser['username']));
        $this->assertFalse($service->isBlocked($account, 'not_blocked_user'));
    }

    #[Test]
    public function it_get_blocked_accounts_returns_latest_first(): void
    {
        $this->markTestIncomplete();
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

        $blockedAccounts = $service->getBlockedAccounts($account);

        $this->assertCount(3, $blockedAccounts);
        // Latest should be first (index 0 has subMinutes(0) = most recent)
        $this->assertEquals('john_doe_123', $blockedAccounts->first()->blocked_username);
    }

    #[Test]
    public function it_blocking_workflow_when_api_block_fails(): void
    {
        $this->markTestIncomplete();
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
        
        // Should still create the local record even if API block fails
        $blockedAccount = $service->blockAccount($account, 'test_user', 'Spam');

        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('test_user', $blockedAccount->blocked_username);
    }
}
