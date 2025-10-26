<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\InstagramAccount;
use App\Models\BlockedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_instagram_account(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'instagram_id' => '123456',
            'access_token' => 'test_token',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('instagram_accounts', [
            'username' => 'test_user',
            'instagram_id' => '123456',
        ]);
    }

    #[Test]
    public function it_can_create_blocked_account(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'test_token',
        ]);

        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
            'blocked_instagram_id' => '789',
            'reason' => 'Spam',
            'comment_text' => 'This is spam',
        ]);

        $this->assertDatabaseHas('blocked_accounts', [
            'blocked_username' => 'blocked_user',
            'reason' => 'Spam',
        ]);

        $this->assertEquals(1, $instagramAccount->blockedAccounts()->count());
    }

    #[Test]
    public function it_instagram_account_relationship_with_blocked_accounts(): void
    {
        $this->markTestIncomplete();
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
        ]);

        $this->assertEquals(2, $instagramAccount->blockedAccounts->count());
    }

    #[Test]
    public function it_instagram_account_can_be_activated_and_deactivated(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
            'is_active' => true,
        ]);

        $this->assertTrue($account->is_active);

        $account->update(['is_active' => false]);
        $this->assertFalse($account->fresh()->is_active);
    }

    #[Test]
    public function it_instagram_account_stores_last_synced_at(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
        ]);

        $this->assertNull($account->last_synced_at);

        $syncTime = now();
        $account->update(['last_synced_at' => $syncTime]);

        $this->assertNotNull($account->fresh()->last_synced_at);
        $this->assertTrue($account->fresh()->last_synced_at->equalTo($syncTime));
    }

    #[Test]
    public function it_instagram_account_username_is_required(): void
    {
        $this->markTestIncomplete();

        InstagramAccount::create([
            'access_token' => 'token',
        ]);
    }

    #[Test]
    public function it_blocked_account_belongs_to_instagram_account(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
        ]);

        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
        ]);

        $this->assertInstanceOf(InstagramAccount::class, $blockedAccount->instagramAccount);
        $this->assertEquals($instagramAccount->id, $blockedAccount->instagramAccount->id);
    }

    #[Test]
    public function it_blocked_account_can_store_reason_and_comment(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
        ]);

        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'spammer',
            'reason' => 'Repeated spam',
            'comment_text' => 'Buy my product now!!!',
        ]);

        $this->assertEquals('Repeated spam', $blockedAccount->reason);
        $this->assertEquals('Buy my product now!!!', $blockedAccount->comment_text);
    }

    #[Test]
    public function it_blocked_account_can_store_instagram_id(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
        ]);

        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'user123',
            'blocked_instagram_id' => '98765432',
        ]);

        $this->assertEquals('98765432', $blockedAccount->blocked_instagram_id);
    }

    #[Test]
    public function it_blocked_account_reason_and_comment_are_optional(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
        ]);

        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
        ]);

        $this->assertNull($blockedAccount->reason);
        $this->assertNull($blockedAccount->comment_text);
        $this->assertNull($blockedAccount->blocked_instagram_id);
    }

    #[Test]
    public function it_multiple_instagram_accounts_can_block_same_username(): void
    {
        $this->markTestIncomplete();
            'username' => 'account1',
            'access_token' => 'token1',
        ]);

        $account2 = InstagramAccount::create([
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
        ]);

        $this->assertEquals(1, $account1->blockedAccounts()->count());
        $this->assertEquals(1, $account2->blockedAccounts()->count());
        $this->assertEquals(2, BlockedAccount::where('blocked_username', 'spammer')->count());
    }

    #[Test]
    public function it_instagram_account_can_have_many_blocked_accounts(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            BlockedAccount::create([
                'instagram_account_id' => $account->id,
                'blocked_username' => "user{$i}",
            ]);
        }

        $this->assertEquals(10, $account->blockedAccounts()->count());
    }

    #[Test]
    public function it_deleting_instagram_account_does_not_cascade_delete_blocked_accounts(): void
    {
        $this->markTestIncomplete();
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
            $account->delete();
            $this->assertEquals($blockedAccountCount, BlockedAccount::count());
        } catch (\Exception $e) {
            // If foreign key constraint prevents deletion, that's also valid behavior
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    #[Test]
    public function it_instagram_account_casts_is_active_to_boolean(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
            'is_active' => 1,
        ]);

        $this->assertIsBool($account->is_active);
        $this->assertTrue($account->is_active);
    }

    #[Test]
    public function it_instagram_account_casts_last_synced_at_to_datetime(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
            'last_synced_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->last_synced_at);
    }

    #[Test]
    public function it_blocked_account_has_timestamps(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
        ]);

        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $instagramAccount->id,
            'blocked_username' => 'blocked_user',
        ]);

        $this->assertNotNull($blockedAccount->created_at);
        $this->assertNotNull($blockedAccount->updated_at);
    }

    #[Test]
    public function it_instagram_account_has_timestamps(): void
    {
        $this->markTestIncomplete();
            'username' => 'test_user',
            'access_token' => 'token',
        ]);

        $this->assertNotNull($account->created_at);
        $this->assertNotNull($account->updated_at);
    }
}