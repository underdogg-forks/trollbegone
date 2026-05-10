<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\BlockedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlockedAccountModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_mass_assignment_of_all_attributes(): void
    {
        /* Arrange */
        $account = Account::factory()->create();

        /* Act */
        $blockedAccount = BlockedAccount::create([
            'instagram_account_id' => $account->id,
            'blocked_username' => 'spammer',
            'blocked_instagram_id' => '99999',
            'reason' => 'Spam',
            'comment_text' => 'Buy my product!',
        ]);

        /* Assert */
        $this->assertEquals('spammer', $blockedAccount->blocked_username);
        $this->assertEquals('99999', $blockedAccount->blocked_instagram_id);
        $this->assertEquals('Spam', $blockedAccount->reason);
        $this->assertDatabaseHas('blocked_accounts', ['blocked_username' => 'spammer']);
    }

    #[Test]
    public function it_belongs_to_instagram_account(): void
    {
        /* Arrange */
        $account = Account::factory()->create();

        /* Act */
        $blockedAccount = BlockedAccount::factory()->forAccount($account)->create();

        /* Assert */
        $this->assertInstanceOf(Account::class, $blockedAccount->account);
        $this->assertEquals($account->id, $blockedAccount->account->id);
    }

    #[Test]
    public function it_can_have_null_blocked_instagram_id(): void
    {
        /* Arrange */

        /* Act */
        $blockedAccount = BlockedAccount::factory()->withoutInstagramId()->create();

        /* Assert */
        $this->assertNull($blockedAccount->blocked_instagram_id);
    }

    #[Test]
    public function it_can_have_null_reason(): void
    {
        /* Arrange */

        /* Act */
        $blockedAccount = BlockedAccount::factory()->create([
            'reason' => null,
        ]);

        /* Assert */
        $this->assertNull($blockedAccount->reason);
    }

    #[Test]
    public function it_can_have_null_comment_text(): void
    {
        /* Arrange */

        /* Act */
        $blockedAccount = BlockedAccount::factory()->create([
            'comment_text' => null,
        ]);

        /* Assert */
        $this->assertNull($blockedAccount->comment_text);
    }

    #[Test]
    public function it_stores_reason_correctly(): void
    {
        /* Arrange */
        $reason = 'Spam and harassment';

        /* Act */
        $blockedAccount = BlockedAccount::factory()->withReason($reason)->create();

        /* Assert */
        $this->assertEquals($reason, $blockedAccount->reason);
    }

    #[Test]
    public function it_stores_comment_text_correctly(): void
    {
        /* Arrange */
        $comment = 'This is the offensive comment';

        /* Act */
        $blockedAccount = BlockedAccount::factory()->withComment($comment)->create();

        /* Assert */
        $this->assertEquals($comment, $blockedAccount->comment_text);
    }

    #[Test]
    public function it_can_be_updated(): void
    {
        /* Arrange */
        $blockedAccount = BlockedAccount::factory()->create([
            'reason' => 'Original reason',
        ]);

        /* Act */
        $blockedAccount->update(['reason' => 'Updated reason']);

        /* Assert */
        $this->assertEquals('Updated reason', $blockedAccount->fresh()->reason);
    }

    #[Test]
    public function it_has_created_at_timestamp(): void
    {
        /* Arrange */

        /* Act */
        $blockedAccount = BlockedAccount::factory()->create();

        /* Assert */
        $this->assertNotNull($blockedAccount->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $blockedAccount->created_at);
    }

    #[Test]
    public function it_has_updated_at_timestamp(): void
    {
        /* Arrange */

        /* Act */
        $blockedAccount = BlockedAccount::factory()->create();

        /* Assert */
        $this->assertNotNull($blockedAccount->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $blockedAccount->updated_at);
    }

    #[Test]
    public function it_factory_generates_valid_data(): void
    {
        /* Arrange */

        /* Act */
        $blockedAccount = BlockedAccount::factory()->create();

        /* Assert */
        $this->assertNotNull($blockedAccount->instagram_account_id);
        $this->assertNotNull($blockedAccount->blocked_username);
        $this->assertIsString($blockedAccount->blocked_username);
    }

    #[Test]
    public function it_multiple_blocked_accounts_can_exist_for_same_instagram_account(): void
    {
        /* Arrange */
        $account = Account::factory()->create();

        /* Act */
        BlockedAccount::factory()->count(2)->forAccount($account)->create();

        /* Assert */
        $this->assertCount(2, $account->blockedAccounts);
        $this->assertDatabaseCount('blocked_accounts', 2);
    }

    #[Test]
    public function it_username_can_be_duplicated_across_different_accounts(): void
    {
        /* Arrange */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        /* Act */
        $blocked1 = BlockedAccount::factory()->forAccount($account1)->create(['blocked_username' => 'same_user']);
        $blocked2 = BlockedAccount::factory()->forAccount($account2)->create(['blocked_username' => 'same_user']);

        /* Assert */
        $this->assertEquals('same_user', $blocked1->blocked_username);
        $this->assertEquals('same_user', $blocked2->blocked_username);
        $this->assertNotEquals($blocked1->instagram_account_id, $blocked2->instagram_account_id);
        $this->assertDatabaseCount('blocked_accounts', 2);
    }
}
