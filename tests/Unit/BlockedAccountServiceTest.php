<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BlockedAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it__block_account_creates_blocked_account_record_with_user_info(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $mockInstagramApi->shouldReceive('getUserInfo')
            ->once()
            ->with($instagramAccount, 'spam_user')
            ->andReturn(['id' => '12345', 'username' => 'spam_user']);

        $mockInstagramApi->shouldReceive('blockUser')
            ->once()
            ->with($instagramAccount, '12345')
            ->andReturn(true);

        $service = new BlockedAccountService($mockInstagramApi);

        $blockedAccount = $service->blockAccount(
            $instagramAccount,
            'spam_user',
            'Spamming comments',
            'Buy my product!'
        );

        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('spam_user', $blockedAccount->blocked_username);
        $this->assertEquals('12345', $blockedAccount->blocked_instagram_id);
        $this->assertEquals('Spamming comments', $blockedAccount->reason);
        $this->assertEquals('Buy my product!', $blockedAccount->comment_text);
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'spam_user',
        ]);
    }

    #[Test]
    public function it__block_account_handles_null_user_info(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $mockInstagramApi->shouldReceive('getUserInfo')
            ->once()
            ->with($instagramAccount, 'nonexistent_user')
            ->andReturn(null);

        $mockInstagramApi->shouldReceive('blockUser')
            ->never();

        $service = new BlockedAccountService($mockInstagramApi);

        $blockedAccount = $service->blockAccount(
            $instagramAccount,
            'nonexistent_user',
            'User not found'
        );

        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('nonexistent_user', $blockedAccount->blocked_username);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        $this->assertEquals('User not found', $blockedAccount->reason);
    }

    #[Test]
    public function it__block_account_handles_user_info_without_id(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $mockInstagramApi->shouldReceive('getUserInfo')
            ->once()
            ->andReturn(['username' => 'partial_user']);

        $mockInstagramApi->shouldReceive('blockUser')
            ->never();

        $service = new BlockedAccountService($mockInstagramApi);

        $blockedAccount = $service->blockAccount(
            $instagramAccount,
            'partial_user'
        );

        $this->assertNull($blockedAccount->blocked_instagram_id);
    }

    #[Test]
    public function it__block_account_creates_record_without_optional_fields(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $mockInstagramApi->shouldReceive('getUserInfo')
            ->once()
            ->andReturn(['id' => '99999']);

        $mockInstagramApi->shouldReceive('blockUser')
            ->once()
            ->andReturn(true);

        $service = new BlockedAccountService($mockInstagramApi);

        $blockedAccount = $service->blockAccount($instagramAccount, 'test_user');

        $this->assertNull($blockedAccount->reason);
        $this->assertNull($blockedAccount->comment_text);
        $this->assertEquals('test_user', $blockedAccount->blocked_username);
    }

    #[Test]
    public function it__is_blocked_returns_true_for_blocked_username(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);

        $this->assertTrue($service->isBlocked($instagramAccount, 'blocked_user'));
    }

    #[Test]
    public function it__is_blocked_returns_false_for_non_blocked_username(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);

        $this->assertFalse($service->isBlocked($instagramAccount, 'non_blocked_user'));
    }

    #[Test]
    public function it__is_blocked_is_case_sensitive(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'BlockedUser',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);

        $this->assertFalse($service->isBlocked($instagramAccount, 'blockeduser'));
    }

    #[Test]
    public function it__is_blocked_checks_specific_instagram_account(): void
    {
        $this->markTestIncomplete();
        $account1 = InstagramAccount::create([
            'username' => 'account1',
            'access_token' => 'token1',
        ]);

        $account2 = InstagramAccount::create([
            'username' => 'account2',
            'access_token' => 'token2',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'blocked_user',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);

        $this->assertTrue($service->isBlocked($account1, 'blocked_user'));
        $this->assertFalse($service->isBlocked($account2, 'blocked_user'));
    }

    #[Test]
    public function it__get_blocked_accounts_returns_all_blocked_accounts_for_instagram_account(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'user1',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'user2',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'user3',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);

        $blockedAccounts = $service->getBlockedAccounts($instagramAccount);

        $this->assertCount(3, $blockedAccounts);
    }

    #[Test]
    public function it__get_blocked_accounts_returns_latest_first(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'oldest',
        ]);

        sleep(1);

        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'newest',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);

        $blockedAccounts = $service->getBlockedAccounts($instagramAccount);

        $this->assertEquals('newest', $blockedAccounts->first()->blocked_username);
        $this->assertEquals('oldest', $blockedAccounts->last()->blocked_username);
    }

    #[Test]
    public function it__get_blocked_accounts_returns_empty_collection_when_no_blocks(): void
    {
        $this->markTestIncomplete();
        $instagramAccount = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);

        $blockedAccounts = $service->getBlockedAccounts($instagramAccount);

        $this->assertCount(0, $blockedAccounts);
    }

    #[Test]
    public function it__get_blocked_accounts_only_returns_accounts_for_specific_instagram_account(): void
    {
        $this->markTestIncomplete();
        $account1 = InstagramAccount::create([
            'username' => 'account1',
            'access_token' => 'token1',
        ]);

        $account2 = InstagramAccount::create([
            'username' => 'account2',
            'access_token' => 'token2',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'user1',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $account2->id,
            'blocked_username' => 'user2',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $account2->id,
            'blocked_username' => 'user3',
        ]);

        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);

        $account1Blocks = $service->getBlockedAccounts($account1);
        $account2Blocks = $service->getBlockedAccounts($account2);

        $this->assertCount(1, $account1Blocks);
        $this->assertCount(2, $account2Blocks);
    }
}