<?php

namespace Tests\Feature;

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

    #[Test]
    public function blocking_account_creates_database_record(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'access_token' => 'test_token',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('spammer', [
            'id' => '12345',
            'username' => 'spammer',
        ]);
        $fakeApiService->setBlockUserResult('12345', true);

        $service = new BlockedAccountService($fakeApiService);
        

        /* Act */
        $service->blockAccount($account, 'spammer', 'Spam comments');
        

        /* Assert */
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $account->id,
            'blocked_username' => 'spammer',
            'blocked_instagram_id' => '12345',
            'reason' => 'Spam comments',
        ]);
        
    }

    #[Test]
    public function checking_if_user_is_blocked_queries_database(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'access_token' => 'test_token',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account->id,
            'blocked_username' => 'blocked_user',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        

        /* Act */
        $service = new BlockedAccountService($fakeApiService);
        

        /* Assert */
        $this->assertTrue($service->isBlocked($account, 'blocked_user'));
        $this->assertFalse($service->isBlocked($account, 'not_blocked_user'));
        
    }

    #[Test]
    public function get_blocked_accounts_returns_only_account_specific_blocks(): void
    {
        /* Arrange */
        $account1 = Account::factory()->create([
            'access_token' => 'token1',
        ]);
        $account2 = Account::factory()->create([
            'access_token' => 'token2',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'user1',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'user2',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account2->id,
            'blocked_username' => 'user3',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        $service = new BlockedAccountService($fakeApiService);
        $account1Blocks = $service->getBlockedAccounts($account1);
        

        /* Act */
        $account2Blocks = $service->getBlockedAccounts($account2);
        

        /* Assert */
        $this->assertCount(2, $account1Blocks);
        $this->assertCount(1, $account2Blocks);
        
    }

    #[Test]
    public function blocking_handles_api_failure_gracefully(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'access_token' => 'test_token',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('target_user', [
            'id' => '12345',
            'username' => 'target_user',
        ]);
        $fakeApiService->setBlockUserResult('12345', false); // Simulate API failure

        $service = new BlockedAccountService($fakeApiService);
        $blockedAccount = $service->blockAccount($account, 'target_user');
        

        /* Act */
        

        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertDatabaseHas('blocked_accounts', [
            'blocked_username' => 'target_user',
        ]);
        
    }

    #[Test]
    public function blocking_user_not_found_still_creates_record(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'access_token' => 'test_token',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('ghost_user', null);

        $service = new BlockedAccountService($fakeApiService);
        

        /* Act */
        $blockedAccount = $service->blockAccount($account, 'ghost_user');
        

        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        $this->assertDatabaseHas('blocked_accounts', [
            'blocked_username' => 'ghost_user',
        ]);
        
    }
}
