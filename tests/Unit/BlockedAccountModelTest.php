<?php

namespace Tests\Unit;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedAccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function testBlockedAccountHasFillableAttributes(): void
    {
        $fillable = (new BlockedAccount())->getFillable();

        $this->assertContains('instagram_account_id', $fillable);
        $this->assertContains('blocked_username', $fillable);
        $this->assertContains('blocked_instagram_id', $fillable);
        $this->assertContains('reason', $fillable);
        $this->assertContains('comment_text', $fillable);
    }

    public function testBlockedAccountBelongsToInstagramAccount(): void
    {
        $account = InstagramAccount::factory()->create();
        $blockedAccount = BlockedAccount::factory()->forInstagramAccount($account)->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $blockedAccount->instagramAccount());
        $this->assertInstanceOf(InstagramAccount::class, $blockedAccount->instagramAccount);
        $this->assertEquals($account->id, $blockedAccount->instagramAccount->id);
    }

    public function testBlockedAccountCanHaveNullBlockedInstagramId(): void
    {
        $blockedAccount = BlockedAccount::factory()->withoutInstagramId()->create();

        $this->assertNull($blockedAccount->blocked_instagram_id);
    }

    public function testBlockedAccountCanHaveNullReason(): void
    {
        $blockedAccount = BlockedAccount::factory()->create([
            'reason' => null,
        ]);

        $this->assertNull($blockedAccount->reason);
    }

    public function testBlockedAccountCanHaveNullCommentText(): void
    {
        $blockedAccount = BlockedAccount::factory()->create([
            'comment_text' => null,
        ]);

        $this->assertNull($blockedAccount->comment_text);
    }

    public function testBlockedAccountStoresReasonCorrectly(): void
    {
        $reason = 'Spam and harassment';
        $blockedAccount = BlockedAccount::factory()->withReason($reason)->create();

        $this->assertEquals($reason, $blockedAccount->reason);
    }

    public function testBlockedAccountStoresCommentTextCorrectly(): void
    {
        $comment = 'This is the offensive comment';
        $blockedAccount = BlockedAccount::factory()->withComment($comment)->create();

        $this->assertEquals($comment, $blockedAccount->comment_text);
    }

    public function testBlockedAccountCanBeUpdated(): void
    {
        $blockedAccount = BlockedAccount::factory()->create([
            'reason' => 'Original reason',
        ]);

        $blockedAccount->update(['reason' => 'Updated reason']);

        $this->assertEquals('Updated reason', $blockedAccount->fresh()->reason);
    }

    public function testBlockedAccountHasCreatedAtTimestamp(): void
    {
        $blockedAccount = BlockedAccount::factory()->create();

        $this->assertNotNull($blockedAccount->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $blockedAccount->created_at);
    }

    public function testBlockedAccountHasUpdatedAtTimestamp(): void
    {
        $blockedAccount = BlockedAccount::factory()->create();

        $this->assertNotNull($blockedAccount->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $blockedAccount->updated_at);
    }

    public function testBlockedAccountFactoryGeneratesValidData(): void
    {
        $blockedAccount = BlockedAccount::factory()->create();

        $this->assertNotNull($blockedAccount->instagram_account_id);
        $this->assertNotNull($blockedAccount->blocked_username);
        $this->assertIsString($blockedAccount->blocked_username);
    }

    public function testMultipleBlockedAccountsCanExistForSameInstagramAccount(): void
    {
        $account = InstagramAccount::factory()->create();

        $blocked1 = BlockedAccount::factory()->forInstagramAccount($account)->create();
        $blocked2 = BlockedAccount::factory()->forInstagramAccount($account)->create();

        $this->assertEquals($account->id, $blocked1->instagram_account_id);
        $this->assertEquals($account->id, $blocked2->instagram_account_id);
        $this->assertNotEquals($blocked1->id, $blocked2->id);
    }

    public function testBlockedAccountUsernameCanBeDuplicatedAcrossDifferentAccounts(): void
    {
        $account1 = InstagramAccount::factory()->create();
        $account2 = InstagramAccount::factory()->create();

        $blocked1 = BlockedAccount::factory()->forInstagramAccount($account1)->create([
            'blocked_username' => 'same_user',
        ]);
        $blocked2 = BlockedAccount::factory()->forInstagramAccount($account2)->create([
            'blocked_username' => 'same_user',
        ]);

        $this->assertEquals('same_user', $blocked1->blocked_username);
        $this->assertEquals('same_user', $blocked2->blocked_username);
        $this->assertNotEquals($blocked1->instagram_account_id, $blocked2->instagram_account_id);
    }
}