<?php

namespace Tests\Feature;

use App\Models\InstagramAccount;
use App\Models\BlockedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_instagram_account(): void
    {
        $account = InstagramAccount::create([
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

    public function test_can_create_blocked_account(): void
    {
        $instagramAccount = InstagramAccount::create([
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

    public function test_instagram_account_relationship_with_blocked_accounts(): void
    {
        $instagramAccount = InstagramAccount::create([
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
}
