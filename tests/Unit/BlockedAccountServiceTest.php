<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\BlockedAccount;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeInstagramApiService;
use Tests\TestCase;

class BlockedAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeInstagramApiService $fakeApi;

    private BlockedAccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeApi = new FakeInstagramApiService;
        $this->service = new BlockedAccountService($this->fakeApi);
    }

    #[Test]
    public function it_creates_blocked_account_record_with_user_info(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);
        $this->fakeApi->setUserInfoResponse('spam_user', ['id' => '12345', 'username' => 'spam_user']);
        $this->fakeApi->setBlockUserResult('12345', true);

        /* Act */
        $blockedAccount = $this->service->blockAccount(
            $instagramAccount,
            'spam_user',
            'Spamming comments',
            'Buy my product!'
        );

        /* Assert */
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
    public function it_handles_null_user_info(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        $this->fakeApi->setUserInfoResponse('nonexistent_user', null);

        /* Act */
        $blockedAccount = $this->service->blockAccount(
            $instagramAccount,
            'nonexistent_user',
            'User not found'
        );

        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals('nonexistent_user', $blockedAccount->blocked_username);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        $this->assertEquals('User not found', $blockedAccount->reason);
    }

    #[Test]
    public function it_handles_user_info_without_id(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        $this->fakeApi->setUserInfoResponse('partial_user', ['username' => 'partial_user']);

        /* Act */
        $blockedAccount = $this->service->blockAccount($instagramAccount, 'partial_user');

        /* Assert */
        $this->assertNull($blockedAccount->blocked_instagram_id);
    }

    #[Test]
    public function it_creates_record_without_optional_fields(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        $this->fakeApi->setUserInfoResponse('test_user', ['id' => '99999']);

        /* Act */
        $blockedAccount = $this->service->blockAccount($instagramAccount, 'test_user');

        /* Assert */
        $this->assertNull($blockedAccount->reason);
        $this->assertNull($blockedAccount->comment_text);
        $this->assertEquals('test_user', $blockedAccount->blocked_username);
    }

    #[Test]
    public function it_returns_true_when_username_is_blocked(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
        ]);

        /* Act */
        $result = $this->service->isBlocked($instagramAccount, 'blocked_user');

        /* Assert */
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_username_is_not_blocked(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        /* Act */
        $result = $this->service->isBlocked($instagramAccount, 'non_blocked_user');

        /* Assert */
        $this->assertFalse($result);
    }

    #[Test]
    public function it_checks_blocked_status_case_sensitively(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'BlockedUser',
        ]);

        /* Act */
        $result = $this->service->isBlocked($instagramAccount, 'blockeduser');

        /* Assert */
        $this->assertFalse($result);
    }

    #[Test]
    public function it_checks_blocked_status_for_specific_instagram_account(): void
    {
        /* Arrange */
        $account1 = Account::factory()->create(['username' => 'account1', 'access_token' => 'token1']);
        $account2 = Account::factory()->create(['username' => 'account2', 'access_token' => 'token2']);
        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'blocked_user',
        ]);

        /* Act */
        $isBlockedOnAccount1 = $this->service->isBlocked($account1, 'blocked_user');
        $isBlockedOnAccount2 = $this->service->isBlocked($account2, 'blocked_user');

        /* Assert */
        $this->assertTrue($isBlockedOnAccount1);
        $this->assertFalse($isBlockedOnAccount2);
    }

    #[Test]
    public function it_returns_all_blocked_accounts_for_instagram_account(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        BlockedAccount::create(['instagram_account_id' => $instagramAccount->id, 'blocked_username' => 'user1']);
        BlockedAccount::create(['instagram_account_id' => $instagramAccount->id, 'blocked_username' => 'user2']);
        BlockedAccount::create(['instagram_account_id' => $instagramAccount->id, 'blocked_username' => 'user3']);

        /* Act */
        $blockedAccounts = $this->service->getBlockedAccounts($instagramAccount);

        /* Assert */
        $this->assertCount(3, $blockedAccounts);
    }

    #[Test]
    public function it_returns_blocked_accounts_latest_first(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'oldest',
            'created_at' => now()->subMinute(),
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'newest',
            'created_at' => now(),
        ]);

        /* Act */
        $blockedAccounts = $this->service->getBlockedAccounts($instagramAccount);

        /* Assert */
        $this->assertEquals('newest', $blockedAccounts->first()->blocked_username);
        $this->assertEquals('oldest', $blockedAccounts->last()->blocked_username);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_blocks_exist(): void
    {
        /* Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);

        /* Act */
        $blockedAccounts = $this->service->getBlockedAccounts($instagramAccount);

        /* Assert */
        $this->assertCount(0, $blockedAccounts);
    }

    #[Test]
    public function it_returns_accounts_only_for_specific_instagram_account(): void
    {
        /* Arrange */
        $account1 = Account::factory()->create(['username' => 'account1', 'access_token' => 'token1']);
        $account2 = Account::factory()->create(['username' => 'account2', 'access_token' => 'token2']);
        BlockedAccount::create(['instagram_account_id' => $account1->id, 'blocked_username' => 'user1']);
        BlockedAccount::create(['instagram_account_id' => $account2->id, 'blocked_username' => 'user2']);
        BlockedAccount::create(['instagram_account_id' => $account2->id, 'blocked_username' => 'user3']);

        /* Act */
        $account1Blocks = $this->service->getBlockedAccounts($account1);
        $account2Blocks = $this->service->getBlockedAccounts($account2);

        /* Assert */
        $this->assertCount(1, $account1Blocks);
        $this->assertCount(2, $account2Blocks);
    }
}
