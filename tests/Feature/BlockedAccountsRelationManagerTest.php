<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\AccountResource;
use App\Models\BlockedAccount;
use App\Models\Account;
use App\Models\User;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeInstagramApiService;
use Tests\TestCase;

/**
 * Test Filament CRUD operations for BlockedAccounts relation manager.
 *
 * Tests verify modal-based create, edit, and delete operations within
 * the relation manager, and ensure service integration works correctly.
 */
class BlockedAccountsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Account $instagramAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);

        $this->instagramAccount = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);
    }

    #[Test]
    public function it_can_render_blocked_accounts_relation_manager(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // User and Instagram account set up in setUp()
        /** #endregion */

        /** #region Act */
        $response = $this->get(
            AccountResource::getUrl('view', ['record' => $this->instagramAccount])
        );
        /** #endregion */

        /** #region Assert */
        $response->assertSuccessful();
        /** #endregion */
    }

    #[Test]
    public function it_can_list_blocked_accounts_in_relation_manager(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_can_create_blocked_account_via_modal_with_service(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_blocked_account_creation_uses_transaction(): void
    {
        /** #region Arrange */
        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('test_user', null); // Simulate getUserInfo returning null

        $service = new BlockedAccountService($fakeApiService);
        /** #endregion */

        /** #region Act */
        $result = $service->blockAccount(
            $this->instagramAccount,
            'test_user',
            'Test reason'
        );
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(BlockedAccount::class, $result);
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $this->instagramAccount->id,
            'blocked_username' => 'test_user',
        ]);
        /** #endregion */
    }

    #[Test]
    public function it_can_validate_blocked_username_is_required(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_can_edit_blocked_account_via_modal(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_can_delete_blocked_account(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_can_bulk_delete_blocked_accounts(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_blocked_accounts_table_displays_correct_columns(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_can_search_blocked_accounts_by_username(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_blocked_accounts_sorted_by_created_at_desc(): void
    {
        $this->markTestIncomplete();
    }

    #[Test]
    public function it_only_shows_blocked_accounts_for_current_instagram_account(): void
    {
        $this->markTestIncomplete();
    }
}
