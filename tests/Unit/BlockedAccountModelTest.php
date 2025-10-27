<?php

namespace Tests\Unit;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlockedAccountModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_guarded_attributes(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $guarded = (new BlockedAccount)->getGuarded();
        /** #endregion */

        /** #region Assert */
        $this->assertEquals([], $guarded);
        /** #endregion */
    }

    #[Test]
    public function it_belongs_to_instagram_account(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::factory()->create();
        /** #endregion */

        /** #region Act */
        $blockedAccount = BlockedAccount::factory()->forInstagramAccount($account)->create();
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $blockedAccount->instagramAccount());
        $this->assertInstanceOf(InstagramAccount::class, $blockedAccount->instagramAccount);
        $this->assertEquals($account->id, $blockedAccount->instagramAccount->id);
        /** #endregion */
    }

    #[Test]
    public function it_can_have_null_blocked_instagram_id(): void
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
    public function it_can_have_null_reason(): void
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
    public function it_can_have_null_comment_text(): void
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
    public function it_stores_reason_correctly(): void
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
    public function it_stores_comment_text_correctly(): void
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
    public function it_can_be_updated(): void
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
    public function it_has_created_at_timestamp(): void
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
    public function it_has_updated_at_timestamp(): void
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
    public function it_factory_generates_valid_data(): void
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
    public function it_allows_multiple_blocked_accounts_for_same_instagram_account(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::factory()->create();
        $blocked1 = BlockedAccount::factory()->forInstagramAccount($account)->create();
        /** #endregion */

        /** #region Act */
        $blocked2 = BlockedAccount::factory()->forInstagramAccount($account)->create();
        /** #endregion */

        /** #region Assert */
        $this->assertEquals($account->id, $blocked1->instagram_account_id);
        $this->assertEquals($account->id, $blocked2->instagram_account_id);
        $this->assertNotEquals($blocked1->id, $blocked2->id);
        /** #endregion */
    }

    #[Test]
    public function it_allows_username_duplication_across_different_accounts(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account1 = InstagramAccount::factory()->create();
        $account2 = InstagramAccount::factory()->create();
        $blocked1 = BlockedAccount::factory()->forInstagramAccount($account1)->create([
            'blocked_username' => 'same_user',
        ]);
        $blocked2 = BlockedAccount::factory()->forInstagramAccount($account2)->create([
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
