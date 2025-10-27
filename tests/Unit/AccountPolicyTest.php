<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\User;
use App\Policies\AccountPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test Account policy for multi-account isolation.
 *
 * Ensures users can only access their own Instagram accounts.
 */
class AccountPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected AccountPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AccountPolicy;
    }

    #[Test]
    public function it_allows_users_to_view_any_accounts(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        /** #endregion */

        /** #region Act */
        $result = $this->policy->viewAny($user);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($result);
        /** #endregion */
    }

    #[Test]
    public function it_allows_users_to_view_their_own_accounts(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        /** #endregion */

        /** #region Act */
        $result = $this->policy->view($user, $account);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($result);
        /** #endregion */
    }

    #[Test]
    public function it_prevents_users_from_viewing_other_users_accounts(): void
    {
        /** #region Arrange */
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user2->id]);
        /** #endregion */

        /** #region Act */
        $result = $this->policy->view($user1, $account);
        /** #endregion */

        /** #region Assert */
        $this->assertFalse($result);
        /** #endregion */
    }

    #[Test]
    public function it_allows_users_to_create_accounts(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        /** #endregion */

        /** #region Act */
        $result = $this->policy->create($user);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($result);
        /** #endregion */
    }

    #[Test]
    public function it_allows_users_to_update_their_own_accounts(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        /** #endregion */

        /** #region Act */
        $result = $this->policy->update($user, $account);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($result);
        /** #endregion */
    }

    #[Test]
    public function it_prevents_users_from_updating_other_users_accounts(): void
    {
        /** #region Arrange */
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user2->id]);
        /** #endregion */

        /** #region Act */
        $result = $this->policy->update($user1, $account);
        /** #endregion */

        /** #region Assert */
        $this->assertFalse($result);
        /** #endregion */
    }

    #[Test]
    public function it_allows_users_to_delete_their_own_accounts(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        /** #endregion */

        /** #region Act */
        $result = $this->policy->delete($user, $account);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($result);
        /** #endregion */
    }

    #[Test]
    public function it_prevents_users_from_deleting_other_users_accounts(): void
    {
        /** #region Arrange */
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user2->id]);
        /** #endregion */

        /** #region Act */
        $result = $this->policy->delete($user1, $account);
        /** #endregion */

        /** #region Assert */
        $this->assertFalse($result);
        /** #endregion */
    }

    #[Test]
    public function it_ensures_multi_tenant_isolation_for_blocking(): void
    {
        /** #region Arrange */
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account1 = Account::factory()->create(['user_id' => $user1->id]);
        $account2 = Account::factory()->create(['user_id' => $user2->id]);
        /** #endregion */

        /** #region Act */
        // User 1 can access their own account
        $canViewOwn = $this->policy->view($user1, $account1);
        $canUpdateOwn = $this->policy->update($user1, $account1);
        $canDeleteOwn = $this->policy->delete($user1, $account1);

        // User 1 cannot access user 2's account
        $canViewOther = $this->policy->view($user1, $account2);
        $canUpdateOther = $this->policy->update($user1, $account2);
        $canDeleteOther = $this->policy->delete($user1, $account2);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($canViewOwn);
        $this->assertTrue($canUpdateOwn);
        $this->assertTrue($canDeleteOwn);
        $this->assertFalse($canViewOther);
        $this->assertFalse($canUpdateOther);
        $this->assertFalse($canDeleteOther);
        /** #endregion */
    }
}
