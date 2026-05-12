<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\BlockedAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_mass_assignment_of_all_attributes(): void
    {
        /* Arrange */
        $user = User::factory()->create();

        /* Act */
        $account = Account::create([
            'user_id' => $user->id,
            'username' => 'test_user',
            'instagram_id' => '12345',
            'access_token' => 'secret_token',
            'is_active' => true,
            'last_synced_at' => null,
        ]);

        /* Assert */
        $this->assertEquals('test_user', $account->username);
        $this->assertEquals('12345', $account->instagram_id);
        $this->assertEquals('secret_token', $account->access_token);
        $this->assertTrue($account->is_active);
        $this->assertDatabaseHas('instagram_accounts', ['username' => 'test_user']);
    }

    #[Test]
    public function it_casts_attributes_correctly(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'is_active' => 1,
            'last_synced_at' => '2024-01-01 12:00:00',
        ]);

        /* Act */
        $fresh = $account->fresh();

        /* Assert */
        $this->assertIsBool($fresh->is_active);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->last_synced_at);
    }

    #[Test]
    public function it_has_blocked_accounts_relationship(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        BlockedAccount::factory()->count(2)->forAccount($account)->create();

        /* Act */
        $blockedAccounts = $account->blockedAccounts;

        /* Assert */
        $this->assertCount(2, $blockedAccounts);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $blockedAccounts);
    }

    #[Test]
    public function it_can_have_null_instagram_id(): void
    {
        /* Arrange */

        /* Act */
        $account = Account::factory()->create([
            'instagram_id' => null,
        ]);

        /* Assert */
        $this->assertNull($account->instagram_id);
        $this->assertDatabaseHas('instagram_accounts', [
            'id' => $account->id,
            'instagram_id' => null,
        ]);
    }

    #[Test]
    public function it_can_have_null_access_token(): void
    {
        /* Arrange */

        /* Act */
        $account = Account::factory()->withoutAccessToken()->create();

        /* Assert */
        $this->assertNull($account->access_token);
    }

    #[Test]
    public function it_defaults_to_active(): void
    {
        /* Arrange */

        /* Act */
        $account = Account::factory()->create();

        /* Assert */
        $this->assertTrue($account->is_active);
    }

    #[Test]
    public function it_can_be_inactive(): void
    {
        /* Arrange */

        /* Act */
        $account = Account::factory()->inactive()->create();

        /* Assert */
        $this->assertFalse($account->is_active);
    }

    #[Test]
    public function it_allows_last_synced_at_to_be_nullable(): void
    {
        /* Arrange */

        /* Act */
        $account = Account::factory()->create([
            'last_synced_at' => null,
        ]);

        /* Assert */
        $this->assertNull($account->last_synced_at);
    }

    #[Test]
    public function it_can_be_updated(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'original_username',
        ]);

        /* Act */
        $account->update(['username' => 'updated_username']);

        /* Assert */
        $this->assertEquals('updated_username', $account->fresh()->username);
    }

    #[Test]
    public function it_factory_creates_unique_usernames(): void
    {
        /* Arrange */

        /* Act */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        /* Assert */
        $this->assertNotEquals($account1->username, $account2->username);
    }

    #[Test]
    public function it_factory_creates_unique_instagram_ids(): void
    {
        /* Arrange */

        /* Act */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        /* Assert */
        $this->assertNotEquals($account1->instagram_id, $account2->instagram_id);
    }

    #[Test]
    public function it_factory_creates_unique_access_tokens(): void
    {
        /* Arrange */

        /* Act */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        /* Assert */
        $this->assertNotEquals($account1->access_token, $account2->access_token);
    }

    #[Test]
    public function it_relationship_loads_blocked_accounts_correctly(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        BlockedAccount::factory()->count(3)->forAccount($account)->create();

        /* Act */
        $blockedAccounts = $account->blockedAccounts;

        /* Assert */
        $this->assertCount(3, $blockedAccounts);
    }
}
