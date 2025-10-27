<?php

namespace Tests\Unit;

use App\Models\BlockedAccount;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlockedAccountModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function blocked_account_has_fillable_attributes(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $fillable = (new BlockedAccount)->getFillable();
        /** #endregion */

        /** #region Assert */
        $this->assertContains('instagram_account_id', $fillable);
        $this->assertContains('blocked_username', $fillable);
        $this->assertContains('blocked_instagram_id', $fillable);
        $this->assertContains('reason', $fillable);
        $this->assertContains('comment_text', $fillable);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_belongs_to_instagram_account(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create();
        /** #endregion */

        /** #region Act */
        $blockedAccount = BlockedAccount::factory()->forAccount($account)->create();
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $blockedAccount->instagramAccount());
        $this->assertInstanceOf(Account::class, $blockedAccount->instagramAccount);
        $this->assertEquals($account->id, $blockedAccount->instagramAccount->id);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_can_have_null_blocked_instagram_id(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $blockedAccount = BlockedAccount::factory()->withoutInstagramId()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertNull($blockedAccount->blocked_instagram_id);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_can_have_null_reason(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $blockedAccount = BlockedAccount::factory()->create([
            'reason' => null,
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertNull($blockedAccount->reason);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_can_have_null_comment_text(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $blockedAccount = BlockedAccount::factory()->create([
            'comment_text' => null,
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertNull($blockedAccount->comment_text);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_stores_reason_correctly(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $reason = 'Spam and harassment';
        /** #endregion */

        /** #region Act */
        $blockedAccount = BlockedAccount::factory()->withReason($reason)->create();
        /** #endregion */

        /** #region Assert */
        $this->assertEquals($reason, $blockedAccount->reason);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_stores_comment_text_correctly(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $comment = 'This is the offensive comment';
        /** #endregion */

        /** #region Act */
        $blockedAccount = BlockedAccount::factory()->withComment($comment)->create();
        /** #endregion */

        /** #region Assert */
        $this->assertEquals($comment, $blockedAccount->comment_text);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_can_be_updated(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $blockedAccount = BlockedAccount::factory()->create([
            'reason' => 'Original reason',
        ]);
        /** #endregion */

        /** #region Act */
        $blockedAccount->update(['reason' => 'Updated reason']);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals('Updated reason', $blockedAccount->fresh()->reason);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_has_created_at_timestamp(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $blockedAccount = BlockedAccount::factory()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertNotNull($blockedAccount->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $blockedAccount->created_at);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_has_updated_at_timestamp(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $blockedAccount = BlockedAccount::factory()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertNotNull($blockedAccount->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $blockedAccount->updated_at);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_factory_generates_valid_data(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $blockedAccount = BlockedAccount::factory()->create();
        /** #endregion */

        /** #region Assert */
        $this->assertNotNull($blockedAccount->instagram_account_id);
        $this->assertNotNull($blockedAccount->blocked_username);
        $this->assertIsString($blockedAccount->blocked_username);
        /** #endregion */
    }

    #[Test]
    public function multiple_blocked_accounts_can_exist_for_same_instagram_account(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = Account::factory()->create();
        $blocked1 = BlockedAccount::factory()->forAccount($account)->create();
        /** #endregion */

        /** #region Act */
        $blocked2 = BlockedAccount::factory()->forAccount($account)->create();
        /** #endregion */

        /** #region Assert */
        $this->assertEquals($account->id, $blocked1->instagram_account_id);
        $this->assertEquals($account->id, $blocked2->instagram_account_id);
        $this->assertNotEquals($blocked1->id, $blocked2->id);
        /** #endregion */
    }

    #[Test]
    public function blocked_account_username_can_be_duplicated_across_different_accounts(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        $blocked1 = BlockedAccount::factory()->forAccount($account1)->create([
            'blocked_username' => 'same_user',
        ]);
        $blocked2 = BlockedAccount::factory()->forAccount($account2)->create([
            'blocked_username' => 'same_user',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals('same_user', $blocked1->blocked_username);
        $this->assertEquals('same_user', $blocked2->blocked_username);
        $this->assertNotEquals($blocked1->instagram_account_id, $blocked2->instagram_account_id);
        /** #endregion */
    }
}
