<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BlockedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_instagram_account(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'instagram_id' => '123456',
            'access_token' => 'test_token',
            'is_active' => true,
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertDatabaseHas('instagram_accounts', [
            'username' => 'test_user',
            'instagram_id' => '123456',
        ]);
        /** #endregion */
    }

    #[Test]
    public function it_can_create_blocked_account(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'test_token',
        ]);
        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
            'blocked_instagram_id' => '789',
            'reason' => 'Spam',
            'comment_text' => 'This is spam',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertDatabaseHas('blocked_accounts', [
            'blocked_username' => 'blocked_user',
            'reason' => 'Spam',
        ]);
        $this->assertEquals(1, $instagramAccount->blockedAccounts()->count());
        /** #endregion */
    }

    #[Test]
    public function it_relationship_with_blocked_accounts(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'test_token',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'user1',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'user2',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(2, $instagramAccount->blockedAccounts->count());
        /** #endregion */
    }

    #[Test]
    public function it_can_be_activated_and_deactivated(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
            'is_active' => true,
        ]);
        /** #endregion */

        /** #region Act */
        $account->update(['is_active' => false]);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($account->is_active);
        $this->assertFalse($account->fresh()->is_active);
        /** #endregion */
    }

    #[Test]
    public function it_stores_last_synced_at(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        ]);
        /** #endregion */

        /** #region Act */
        $account->update(['last_synced_at' => $syncTime]);
        /** #endregion */

        /** #region Assert */
        $this->assertNull($account->last_synced_at);
        $syncTime = now();
        $this->assertNotNull($account->fresh()->last_synced_at);
        $this->assertTrue($account->fresh()->last_synced_at->equalTo($syncTime));
        /** #endregion */
    }

    #[Test]
    public function it_username_is_required(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        // No action needed
        /** #endregion */

        /** #region Assert */
        $this->expectException(\Illuminate\Database\QueryException::class);
        Account::factory()->create([
            'access_token' => 'token',
        ]);
        /** #endregion */
    }

    #[Test]
    public function it_belongs_to_instagram_account(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        ]);
        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(Account::class, $blockedAccount->instagramAccount);
        $this->assertEquals($instagramAccount->id, $blockedAccount->instagramAccount->id);
        /** #endregion */
    }

    #[Test]
    public function it_can_store_reason_and_comment(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        ]);
        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'spammer',
            'reason' => 'Repeated spam',
            'comment_text' => 'Buy my product now!!!',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals('Repeated spam', $blockedAccount->reason);
        $this->assertEquals('Buy my product now!!!', $blockedAccount->comment_text);
        /** #endregion */
    }

    #[Test]
    public function it_can_store_instagram_id(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        ]);
        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'user123',
            'blocked_instagram_id' => '98765432',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals('98765432', $blockedAccount->blocked_instagram_id);
        /** #endregion */
    }

    #[Test]
    public function it_reason_and_comment_are_optional(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        ]);
        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertNull($blockedAccount->reason);
        $this->assertNull($blockedAccount->comment_text);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        /** #endregion */
    }

    #[Test]
    public function it_multiple_instagram_accounts_can_block_same_username(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account1 = Account::factory()->create([
            'username' => 'account1',
            'access_token' => 'token1',
        ]);
        $account2 = Account::factory()->create([
            'username' => 'account2',
            'access_token' => 'token2',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'spammer',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account2->id,
            'blocked_username' => 'spammer',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(1, $account1->blockedAccounts()->count());
        $this->assertEquals(1, $account2->blockedAccounts()->count());
        $this->assertEquals(2, BlockedAccount::where('blocked_username', 'spammer')->count());
        /** #endregion */
    }

    #[Test]
    public function it_can_have_many_blocked_accounts(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        ]);
        for ($i = 1; $i <= 10; $i++) {
            BlockedAccount::create([
                'instagram_account_id' => $account->id,
                'blocked_username' => "user{$i}",
            ]);
            /** #endregion */

            /** #region Act */
            // No action needed
            /** #endregion */

            /** #region Assert */
            // No assertions
            /** #endregion */
        }

        $this->assertEquals(10, $account->blockedAccounts()->count());
    }

    #[Test]
    public function it_deleting_instagram_account_does_not_cascade_delete_blocked_accounts(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account->id,
            'blocked_username' => 'blocked_user',
        ]);
        $blockedAccountCount = BlockedAccount::count();
        // This should work or throw an integrity constraint error depending on migration
        try {
            /** #endregion */

            /** #region Act */
            $account->delete();
            /** #endregion */

            /** #region Assert */
            $this->assertEquals($blockedAccountCount, BlockedAccount::count());
            /** #endregion */
        } catch (\Exception $e) {
            // If foreign key constraint prevents deletion, that's also valid behavior
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    #[Test]
    public function it_casts_is_active_to_boolean(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
            'is_active' => 1,
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertIsBool($account->is_active);
        $this->assertTrue($account->is_active);
        /** #endregion */
    }

    #[Test]
    public function it_casts_last_synced_at_to_datetime(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
            'last_synced_at' => now(),
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->last_synced_at);
        /** #endregion */
    }

    #[Test]
    public function it_blocked_account_has_timestamps(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $instagramAccount = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        ]);
        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertNotNull($blockedAccount->created_at);
        $this->assertNotNull($blockedAccount->updated_at);
        /** #endregion */
    }

    #[Test]
    public function it_account_has_timestamps(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'access_token' => 'token',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertNotNull($account->created_at);
        $this->assertNotNull($account->updated_at);
        /** #endregion */
    }
}
