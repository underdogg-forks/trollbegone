<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedAccountModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_blocked_account_has_fillable_attributes(): void
    {
        $this->markTestIncomplete();
        $fillable = (new BlockedAccount())->getFillable();

        $this->assertContains('instagram_account_id', $fillable);
        $this->assertContains('blocked_username', $fillable);
        $this->assertContains('blocked_instagram_id', $fillable);
        $this->assertContains('reason', $fillable);
        $this->assertContains('comment_text', $fillable);
    }

    #[Test]
    public function it_blocked_account_belongs_to_instagram_account(): void
    {
        $this->markTestIncomplete();
        $account = InstagramAccount::factory()->create();
        $blockedAccount = BlockedAccount::factory()->forInstagramAccount($account)->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $blockedAccount->instagramAccount());
        $this->assertInstanceOf(InstagramAccount::class, $blockedAccount->instagramAccount);
        $this->assertEquals($account->id, $blockedAccount->instagramAccount->id);
    }

    #[Test]
    public function it_blocked_account_can_have_null_blocked_instagram_id(): void
    {
        $this->markTestIncomplete();
        $blockedAccount = BlockedAccount::factory()->withoutInstagramId()->create();

        $this->assertNull($blockedAccount->blocked_instagram_id);
    }

    #[Test]
    public function it_blocked_account_can_have_null_reason(): void
    {
        $this->markTestIncomplete();
        $blockedAccount = BlockedAccount::factory()->create([
            'reason' => null,
        ]);

        $this->assertNull($blockedAccount->reason);
    }

    #[Test]
    public function it_blocked_account_can_have_null_comment_text(): void
    {
        $this->markTestIncomplete();
        $blockedAccount = BlockedAccount::factory()->create([
            'comment_text' => null,
        ]);

        $this->assertNull($blockedAccount->comment_text);
    }

    #[Test]
    public function it_blocked_account_stores_reason_correctly(): void
    {
        $this->markTestIncomplete();
        $reason = 'Spam and harassment';
        $blockedAccount = BlockedAccount::factory()->withReason($reason)->create();

        $this->assertEquals($reason, $blockedAccount->reason);
    }

    #[Test]
    public function it_blocked_account_stores_comment_text_correctly(): void
    {
        $this->markTestIncomplete();
        $comment = 'This is the offensive comment';
        $blockedAccount = BlockedAccount::factory()->withComment($comment)->create();

        $this->assertEquals($comment, $blockedAccount->comment_text);
    }

    #[Test]
    public function it_blocked_account_can_be_updated(): void
    {
        $this->markTestIncomplete();
        $blockedAccount = BlockedAccount::factory()->create([
            'reason' => 'Original reason',
        ]);

        $blockedAccount->update(['reason' => 'Updated reason']);

        $this->assertEquals('Updated reason', $blockedAccount->fresh()->reason);
    }

    #[Test]
    public function it_blocked_account_has_created_at_timestamp(): void
    {
        $this->markTestIncomplete();
        $blockedAccount = BlockedAccount::factory()->create();

        $this->assertNotNull($blockedAccount->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $blockedAccount->created_at);
    }

    #[Test]
    public function it_blocked_account_has_updated_at_timestamp(): void
    {
        $this->markTestIncomplete();
        $blockedAccount = BlockedAccount::factory()->create();

        $this->assertNotNull($blockedAccount->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $blockedAccount->updated_at);
    }

    #[Test]
    public function it_blocked_account_factory_generates_valid_data(): void
    {
        $this->markTestIncomplete();
        $blockedAccount = BlockedAccount::factory()->create();

        $this->assertNotNull($blockedAccount->instagram_account_id);
        $this->assertNotNull($blockedAccount->blocked_username);
        $this->assertIsString($blockedAccount->blocked_username);
    }

    #[Test]
    public function it_multiple_blocked_accounts_can_exist_for_same_instagram_account(): void
    {
        $this->markTestIncomplete();
        $account = InstagramAccount::factory()->create();

        $blocked1 = BlockedAccount::factory()->forInstagramAccount($account)->create();
        $blocked2 = BlockedAccount::factory()->forInstagramAccount($account)->create();

        $this->assertEquals($account->id, $blocked1->instagram_account_id);
        $this->assertEquals($account->id, $blocked2->instagram_account_id);
        $this->assertNotEquals($blocked1->id, $blocked2->id);
    }

    #[Test]
    public function it_blocked_account_username_can_be_duplicated_across_different_accounts(): void
    {
        $this->markTestIncomplete();
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