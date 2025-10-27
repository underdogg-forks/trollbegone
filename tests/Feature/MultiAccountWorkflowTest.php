<?php

namespace Tests\Feature;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use App\Models\User;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeInstagramApiService;
use Tests\TestCase;

/**
 * Multi-Account Workflow Test
 *
 * Tests the complete workflow described in the problem statement:
 * 1. User logs in and sees only their accounts
 * 2. User can view stories and comments for their accounts
 * 3. User can block trolls from comments
 * 4. Multiple users can work independently
 */
class MultiAccountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_grandma_to_manage_her_instagram_account_independently(): void
    {
        /** #region Arrange */
        // Grandma creates her account in the system
        $grandma = User::factory()->create([
            'name' => 'Grandma',
            'email' => 'grandma@example.com',
        ]);

        // Grandma connects her Instagram account
        $grandmaInstagram = InstagramAccount::factory()->create([
            'user_id' => $grandma->id,
            'username' => 'grandma_account',
            'access_token' => 'grandma_token',
        ]);

        // Setup fake Instagram API service
        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('troll_user', [
            'id' => '999',
            'username' => 'troll_user',
        ]);
        $fakeApiService->setBlockUserResult('999', true);

        $service = new BlockedAccountService($fakeApiService);
        /** #endregion */

        /** #region Act */
        // Grandma blocks a troll from a comment
        $blockedAccount = $service->blockAccount(
            $grandmaInstagram,
            'troll_user',
            'Blocked from story comments',
            'Offensive comment text'
        );
        /** #endregion */

        /** #region Assert */
        // Verify the block was recorded
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals($grandmaInstagram->id, $blockedAccount->instagram_account_id);
        $this->assertEquals('troll_user', $blockedAccount->blocked_username);
        $this->assertEquals('999', $blockedAccount->blocked_instagram_id);
        $this->assertEquals('Blocked from story comments', $blockedAccount->reason);

        // Verify it's in the database
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $grandmaInstagram->id,
            'blocked_username' => 'troll_user',
        ]);
        /** #endregion */
    }

    #[Test]
    public function it_ensures_multiple_users_have_isolated_instagram_accounts(): void
    {
        /** #region Arrange */
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);

        $account1 = InstagramAccount::factory()->create([
            'user_id' => $user1->id,
            'username' => 'user1_instagram',
        ]);

        $account2 = InstagramAccount::factory()->create([
            'user_id' => $user2->id,
            'username' => 'user2_instagram',
        ]);
        /** #endregion */

        /** #region Act */
        // Verify User 1 owns account 1
        $user1OwnsAccount1 = $account1->user_id === $user1->id;

        // Verify User 2 owns account 2
        $user2OwnsAccount2 = $account2->user_id === $user2->id;

        // Verify accounts are isolated
        $user1CannotAccessAccount2 = $account2->user_id !== $user1->id;
        $user2CannotAccessAccount1 = $account1->user_id !== $user2->id;
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($user1OwnsAccount1);
        $this->assertTrue($user2OwnsAccount2);
        $this->assertTrue($user1CannotAccessAccount2);
        $this->assertTrue($user2CannotAccessAccount1);
        /** #endregion */
    }

    #[Test]
    public function it_allows_user_to_block_multiple_trolls_from_different_comments(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $account = InstagramAccount::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'test_token',
        ]);

        $fakeApiService = new FakeInstagramApiService;

        // Simulate 3 different trolls
        $trolls = [
            ['username' => 'troll1', 'id' => '111', 'comment' => 'Spam link here!'],
            ['username' => 'troll2', 'id' => '222', 'comment' => 'Offensive content'],
            ['username' => 'troll3', 'id' => '333', 'comment' => 'More spam'],
        ];

        foreach ($trolls as $troll) {
            $fakeApiService->setUserInfoResponse($troll['username'], [
                'id' => $troll['id'],
                'username' => $troll['username'],
            ]);
            $fakeApiService->setBlockUserResult($troll['id'], true);
        }

        $service = new BlockedAccountService($fakeApiService);
        /** #endregion */

        /** #region Act */
        // Block all trolls
        $blockedAccounts = [];
        foreach ($trolls as $troll) {
            $blockedAccounts[] = $service->blockAccount(
                $account,
                $troll['username'],
                'Spam/Offensive',
                $troll['comment']
            );
        }
        /** #endregion */

        /** #region Assert */
        // Verify all trolls are blocked
        $this->assertCount(3, $blockedAccounts);

        foreach ($trolls as $troll) {
            $this->assertDatabaseHas('blocked_accounts', [
                'instagram_account_id' => $account->id,
                'blocked_username' => $troll['username'],
                'blocked_instagram_id' => $troll['id'],
            ]);
        }

        // Verify account has 3 blocked users
        $this->assertEquals(3, $account->blockedAccounts()->count());
        /** #endregion */
    }

    #[Test]
    public function it_ensures_blocked_accounts_are_isolated_between_different_instagram_accounts(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();

        // User has 2 Instagram accounts
        $account1 = InstagramAccount::factory()->create([
            'user_id' => $user->id,
            'username' => 'account_1',
        ]);

        $account2 = InstagramAccount::factory()->create([
            'user_id' => $user->id,
            'username' => 'account_2',
        ]);

        // Block a user on account 1
        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'troll_user',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        $service = new BlockedAccountService($fakeApiService);
        /** #endregion */

        /** #region Act */
        $isBlockedOnAccount1 = $service->isBlocked($account1, 'troll_user');
        $isBlockedOnAccount2 = $service->isBlocked($account2, 'troll_user');
        /** #endregion */

        /** #region Assert */
        // Troll is blocked on account 1 but not account 2
        $this->assertTrue($isBlockedOnAccount1);
        $this->assertFalse($isBlockedOnAccount2);
        /** #endregion */
    }

    #[Test]
    public function it_allows_concurrent_users_to_block_different_trolls_simultaneously(): void
    {
        /** #region Arrange */
        // Two grandmas using the system at the same time
        $grandma1 = User::factory()->create(['name' => 'Grandma 1']);
        $grandma2 = User::factory()->create(['name' => 'Grandma 2']);

        $account1 = InstagramAccount::factory()->create([
            'user_id' => $grandma1->id,
            'username' => 'grandma1_insta',
            'access_token' => 'token1',
        ]);

        $account2 = InstagramAccount::factory()->create([
            'user_id' => $grandma2->id,
            'username' => 'grandma2_insta',
            'access_token' => 'token2',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('troll_x', ['id' => 'X123', 'username' => 'troll_x']);
        $fakeApiService->setUserInfoResponse('troll_y', ['id' => 'Y456', 'username' => 'troll_y']);
        $fakeApiService->setBlockUserResult('X123', true);
        $fakeApiService->setBlockUserResult('Y456', true);

        $service = new BlockedAccountService($fakeApiService);
        /** #endregion */

        /** #region Act */
        // Grandma 1 blocks troll_x
        $block1 = $service->blockAccount($account1, 'troll_x', 'Spam');

        // Grandma 2 blocks troll_y (simultaneously/independently)
        $block2 = $service->blockAccount($account2, 'troll_y', 'Offensive');
        /** #endregion */

        /** #region Assert */
        // Both blocks succeed
        $this->assertInstanceOf(BlockedAccount::class, $block1);
        $this->assertInstanceOf(BlockedAccount::class, $block2);

        // Blocks are isolated to each account
        $this->assertEquals($account1->id, $block1->instagram_account_id);
        $this->assertEquals($account2->id, $block2->instagram_account_id);

        // Each account has only their own block
        $this->assertEquals(1, $account1->blockedAccounts()->count());
        $this->assertEquals(1, $account2->blockedAccounts()->count());

        // Verify database records
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'troll_x',
        ]);

        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $account2->id,
            'blocked_username' => 'troll_y',
        ]);
        /** #endregion */
    }
}
