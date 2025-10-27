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
    public function instagram_account_has_fillable_attributes(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = new Account;
        /** #endregion */

        /** #region Act */
        $fillable = $account->getFillable();
        $guarded = $account->getGuarded();
        /** #endregion */

        /** #region Assert */
        $this->assertContains('username', $fillable);
        $this->assertContains('instagram_id', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('last_synced_at', $fillable);
        $this->assertNotContains('access_token', $fillable);
        $this->assertContains('access_token', $guarded);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_casts_attributes_correctly(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account = Account::factory()->create([
            'is_active' => 1,
            'last_synced_at' => '2024-01-01 12:00:00',
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertIsBool($account->is_active);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->last_synced_at);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_has_blocked_accounts_relationship(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create();
        /** #endregion */

        /** #region Act */
        $relationship = $account->blockedAccounts();
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relationship);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_can_have_null_instagram_id(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account = Account::factory()->create([
            'instagram_id' => null,
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertNull($account->instagram_id);
        $this->assertDatabaseHas('instagram_accounts', [
            'id' => $account->id,
            'instagram_id' => null,
        ]);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_can_have_null_access_token(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account = Account::factory()->withoutAccessToken()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertNull($account->access_token);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_defaults_to_active(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account = Account::factory()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($account->is_active);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_can_be_inactive(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account = Account::factory()->inactive()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertFalse($account->is_active);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_last_synced_at_is_nullable(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account = Account::factory()->create([
            'last_synced_at' => null,
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertNull($account->last_synced_at);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_can_be_updated(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'original_username',
        ]);
        /** #endregion */

        /** #region Act */
        $account->update(['username' => 'updated_username']);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals('updated_username', $account->fresh()->username);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_factory_creates_unique_usernames(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertNotEquals($account1->username, $account2->username);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_factory_creates_unique_instagram_ids(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertNotEquals($account1->instagram_id, $account2->instagram_id);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_factory_creates_unique_access_tokens(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertNotEquals($account1->access_token, $account2->access_token);
        /** #endregion */
    }

    #[Test]
    public function instagram_account_relationship_loads_blocked_accounts_correctly(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create();
        BlockedAccount::factory()->count(3)->forAccount($account)->create();
        /** #endregion */

        /** #region Act */
        $blockedAccounts = $account->blockedAccounts;
        /** #endregion */

        /** #region Assert */
        $this->assertCount(3, $blockedAccounts);
        /** #endregion */
    }
}
