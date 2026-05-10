<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\BlockedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $this->markTestIncomplete();

        /* Arrange */
        $account = new Account;

        /* Act */
        $fillable = $account->getFillable();
        $guarded = $account->getGuarded();

        /* Assert */
        $this->assertContains('username', $fillable);
        $this->assertContains('instagram_id', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('last_synced_at', $fillable);
        $this->assertNotContains('access_token', $fillable);
        $this->assertContains('access_token', $guarded);
    }

    #[Test]
    public function it_casts_attributes_correctly(): void
    {
        $this->markTestIncomplete();

        /* Arrange */

        /* Act */
        $account = Account::factory()->create([
            'is_active' => 1,
            'last_synced_at' => '2024-01-01 12:00:00',
        ]);

        /* Assert */
        $this->assertIsBool($account->is_active);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->last_synced_at);
    }

    #[Test]
    public function it_has_blocked_accounts_relationship(): void
    {
        $this->markTestIncomplete();

        /* Arrange */
        $account = Account::factory()->create();

        /* Act */
        $relationship = $account->blockedAccounts();

        /* Assert */
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relationship);
    }

    #[Test]
    public function it_can_have_null_instagram_id(): void
    {
        $this->markTestIncomplete();

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
        $this->markTestIncomplete();

        /* Arrange */

        /* Act */
        $account = Account::factory()->withoutAccessToken()->create();

        /* Assert */
        $this->assertNull($account->access_token);
    }

    #[Test]
    public function it_defaults_to_active(): void
    {
        $this->markTestIncomplete();

        /* Arrange */

        /* Act */
        $account = Account::factory()->create();

        /* Assert */
        $this->assertTrue($account->is_active);
    }

    #[Test]
    public function it_can_be_inactive(): void
    {
        $this->markTestIncomplete();

        /* Arrange */

        /* Act */
        $account = Account::factory()->inactive()->create();

        /* Assert */
        $this->assertFalse($account->is_active);
    }

    #[Test]
    public function it_last_synced_at_is_nullable(): void
    {
        $this->markTestIncomplete();

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
        $this->markTestIncomplete();

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
        $this->markTestIncomplete();

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
        $this->markTestIncomplete();

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
        $this->markTestIncomplete();

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
        $this->markTestIncomplete();

        /* Arrange */
        $account = Account::factory()->create();
        BlockedAccount::factory()->count(3)->forAccount($account)->create();

        /* Act */
        $blockedAccounts = $account->blockedAccounts;

        /* Assert */
        $this->assertCount(3, $blockedAccounts);
    }
}
