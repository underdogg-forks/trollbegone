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
        /** #region Arrange */
        $account = Account::factory()->create([
            'access_token' => 'test_token',
        ]);

        // Use fake API service with fixture data
        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('spammer', [
            'id' => '12345',
            'username' => 'spammer',
        ]);
        $fakeApiService->setBlockUserResult('12345', true);

        $service = new BlockedAccountService($fakeApiService);
        /** #endregion */

        /** #region Act */
        $service->blockAccount($account, 'spammer', 'Spam comments');
        /** #endregion */

        /** #region Assert */
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $account->id,
            'blocked_username' => 'spammer',
            'blocked_instagram_id' => '12345',
            'reason' => 'Spam comments',
        ]);
        /** #endregion */
    }

    #[Test]
    public function checking_if_user_is_blocked_queries_database(): void
    {
        /** #region Arrange */
        $account = Account::factory()->create([
            'access_token' => 'test_token',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account->id,
            'blocked_username' => 'blocked_user',
        ]);

        // Use fake API service (no API calls needed for this test)
        $fakeApiService = new FakeInstagramApiService;
        /** #endregion */

        /** #region Act */
        $service = new BlockedAccountService($fakeApiService);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($service->isBlocked($account, 'blocked_user'));
        $this->assertFalse($service->isBlocked($account, 'not_blocked_user'));
        /** #endregion */
    }

    #[Test]
    public function get_blocked_accounts_returns_only_account_specific_blocks(): void
    {
        /** #region Arrange */
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

        // Use fake API service
        $fakeApiService = new FakeInstagramApiService;
        $service = new BlockedAccountService($fakeApiService);
        $account1Blocks = $service->getBlockedAccounts($account1);
        /** #endregion */

        /** #region Act */
        $account2Blocks = $service->getBlockedAccounts($account2);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(2, $account1Blocks);
        $this->assertCount(1, $account2Blocks);
        /** #endregion */
    }

    #[Test]
    public function blocking_handles_api_failure_gracefully(): void
    {
        /** #region Arrange */
        $account = Account::factory()->create([
            'access_token' => 'test_token',
        ]);

        // Use fake API service with user info but block failure
        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('target_user', [
            'id' => '12345',
            'username' => 'target_user',
        ]);
        $fakeApiService->setBlockUserResult('12345', false); // Simulate API failure

        $service = new BlockedAccountService($fakeApiService);
        $blockedAccount = $service->blockAccount($account, 'target_user');
        /** #endregion */

        /** #region Act */
        // Record should still be created even if API call fails
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertDatabaseHas('blocked_accounts', [
            'blocked_username' => 'target_user',
        ]);
        /** #endregion */
    }

    #[Test]
    public function blocking_user_not_found_still_creates_record(): void
    {
        /** #region Arrange */
        $account = Account::factory()->create([
            'access_token' => 'test_token',
        ]);

        // Use fake API service with no user info (user not found)
        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('ghost_user', null);

        $service = new BlockedAccountService($fakeApiService);
        /** #endregion */

        /** #region Act */
        $blockedAccount = $service->blockAccount($account, 'ghost_user');
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        $this->assertDatabaseHas('blocked_accounts', [
            'blocked_username' => 'ghost_user',
        ]);
        /** #endregion */
    }
}
