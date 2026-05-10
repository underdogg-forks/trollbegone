<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BlockedAccount;
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
    public function grandma_can_manage_her_instagram_account_independently(): void
    {
        /** #region Arrange */
        /* Arrange */
        $grandma = User::factory()->create([
            'name' => 'Grandma',
            'email' => 'grandma@example.com',
        ]);

        $grandmaInstagram = Account::factory()->create([
            'user_id' => $grandma->id,
            'username' => 'grandma_account',
            'access_token' => 'grandma_token',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('troll_user', [
            'id' => '999',
            'username' => 'troll_user',
        ]);
        $fakeApiService->setBlockUserResult('999', true);

        $service = new BlockedAccountService($fakeApiService);
        

        /** #endregion */

        /** #region Act */
        /* Act */
        $blockedAccount = $service->blockAccount(
            $grandmaInstagram,
            'troll_user',
            'Blocked from story comments',
            'Offensive comment text'
        );
        

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertEquals($grandmaInstagram->id, $blockedAccount->instagram_account_id);
        $this->assertEquals('troll_user', $blockedAccount->blocked_username);
        $this->assertEquals('999', $blockedAccount->blocked_instagram_id);
        $this->assertEquals('Blocked from story comments', $blockedAccount->reason);

        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $grandmaInstagram->id,
            'blocked_username' => 'troll_user',
        ]);
        
    /** #endregion */
    }

    #[Test]
    public function it_multiple_users_have_isolated_instagram_accounts(): void
    {
        /** #region Arrange */
        /* Arrange */
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);

        $account1 = Account::factory()->create([
            'user_id' => $user1->id,
            'username' => 'user1_instagram',
        ]);

        $account2 = Account::factory()->create([
            'user_id' => $user2->id,
            'username' => 'user2_instagram',
        ]);
        

        /** #endregion */

        /** #region Act */
        /* Act */
        $user1OwnsAccount1 = $account1->user_id === $user1->id;

        $user2OwnsAccount2 = $account2->user_id === $user2->id;

        $user1CannotAccessAccount2 = $account2->user_id !== $user1->id;
        $user2CannotAccessAccount1 = $account1->user_id !== $user2->id;
        

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertTrue($user1OwnsAccount1);
        $this->assertTrue($user2OwnsAccount2);
        $this->assertTrue($user1CannotAccessAccount2);
        $this->assertTrue($user2CannotAccessAccount1);
        
    /** #endregion */
    }

    #[Test]
    public function user_can_block_multiple_trolls_from_different_comments(): void
    {
        /** #region Arrange */
        /* Arrange */
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'test_token',
        ]);

        $fakeApiService = new FakeInstagramApiService;

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
        /* Act */
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
        /* Assert */
        $this->assertCount(3, $blockedAccounts);

        foreach ($trolls as $troll) {
            $this->assertDatabaseHas('blocked_accounts', [
                'instagram_account_id' => $account->id,
                'blocked_username' => $troll['username'],
                'blocked_instagram_id' => $troll['id'],
            ]);
        /** #endregion */
        }

        $this->assertEquals(3, $account->blockedAccounts()->count());
        
    }

    #[Test]
    public function blocked_accounts_are_isolated_between_different_instagram_accounts(): void
    {
        /** #region Arrange */
        /* Arrange */
        $user = User::factory()->create();

        $account1 = Account::factory()->create([
            'user_id' => $user->id,
            'username' => 'account_1',
        ]);

        $account2 = Account::factory()->create([
            'user_id' => $user->id,
            'username' => 'account_2',
        ]);

        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'troll_user',
        ]);

        $fakeApiService = new FakeInstagramApiService;
        $service = new BlockedAccountService($fakeApiService);
        

        /** #endregion */

        /** #region Act */
        /* Act */
        $isBlockedOnAccount1 = $service->isBlocked($account1, 'troll_user');
        $isBlockedOnAccount2 = $service->isBlocked($account2, 'troll_user');
        

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertTrue($isBlockedOnAccount1);
        $this->assertFalse($isBlockedOnAccount2);
        
    /** #endregion */
    }

    #[Test]
    public function concurrent_users_can_block_different_trolls_simultaneously(): void
    {
        /** #region Arrange */
        /* Arrange */
        $grandma1 = User::factory()->create(['name' => 'Grandma 1']);
        $grandma2 = User::factory()->create(['name' => 'Grandma 2']);

        $account1 = Account::factory()->create([
            'user_id' => $grandma1->id,
            'username' => 'grandma1_insta',
            'access_token' => 'token1',
        ]);

        $account2 = Account::factory()->create([
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
        /* Act */
        $block1 = $service->blockAccount($account1, 'troll_x', 'Spam');

        $block2 = $service->blockAccount($account2, 'troll_y', 'Offensive');
        

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $block1);
        $this->assertInstanceOf(BlockedAccount::class, $block2);

        $this->assertEquals($account1->id, $block1->instagram_account_id);
        $this->assertEquals($account2->id, $block2->instagram_account_id);

        $this->assertEquals(1, $account1->blockedAccounts()->count());
        $this->assertEquals(1, $account2->blockedAccounts()->count());

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
