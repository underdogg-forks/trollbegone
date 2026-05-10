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
        /* Arrange */
        $user = User::factory()->create();
        

        /* Act */
        $result = $this->policy->viewAny($user);
        

        /* Assert */
        $this->assertTrue($result);
        
    }

    #[Test]
    public function it_allows_users_to_view_their_own_accounts(): void
    {
        /* Arrange */
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        

        /* Act */
        $result = $this->policy->view($user, $account);
        

        /* Assert */
        $this->assertTrue($result);
        
    }

    #[Test]
    public function it_prevents_users_from_viewing_other_users_accounts(): void
    {
        /* Arrange */
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user2->id]);
        

        /* Act */
        $result = $this->policy->view($user1, $account);
        

        /* Assert */
        $this->assertFalse($result);
        
    }

    #[Test]
    public function it_allows_users_to_create_accounts(): void
    {
        /* Arrange */
        $user = User::factory()->create();
        

        /* Act */
        $result = $this->policy->create($user);
        

        /* Assert */
        $this->assertTrue($result);
        
    }

    #[Test]
    public function it_allows_users_to_update_their_own_accounts(): void
    {
        /* Arrange */
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        

        /* Act */
        $result = $this->policy->update($user, $account);
        

        /* Assert */
        $this->assertTrue($result);
        
    }

    #[Test]
    public function it_prevents_users_from_updating_other_users_accounts(): void
    {
        /* Arrange */
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user2->id]);
        

        /* Act */
        $result = $this->policy->update($user1, $account);
        

        /* Assert */
        $this->assertFalse($result);
        
    }

    #[Test]
    public function it_allows_users_to_delete_their_own_accounts(): void
    {
        /* Arrange */
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        

        /* Act */
        $result = $this->policy->delete($user, $account);
        

        /* Assert */
        $this->assertTrue($result);
        
    }

    #[Test]
    public function it_prevents_users_from_deleting_other_users_accounts(): void
    {
        /* Arrange */
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user2->id]);
        

        /* Act */
        $result = $this->policy->delete($user1, $account);
        

        /* Assert */
        $this->assertFalse($result);
        
    }

    #[Test]
    public function it_ensures_multi_tenant_isolation_for_blocking(): void
    {
        /* Arrange */
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account1 = Account::factory()->create(['user_id' => $user1->id]);
        $account2 = Account::factory()->create(['user_id' => $user2->id]);
        

        /* Act */
        $canViewOwn = $this->policy->view($user1, $account1);
        $canUpdateOwn = $this->policy->update($user1, $account1);
        $canDeleteOwn = $this->policy->delete($user1, $account1);

        $canViewOther = $this->policy->view($user1, $account2);
        $canUpdateOther = $this->policy->update($user1, $account2);
        $canDeleteOther = $this->policy->delete($user1, $account2);
        

        /* Assert */
        $this->assertTrue($canViewOwn);
        $this->assertTrue($canUpdateOwn);
        $this->assertTrue($canDeleteOwn);
        $this->assertFalse($canViewOther);
        $this->assertFalse($canUpdateOther);
        $this->assertFalse($canDeleteOther);
        
    }
}
