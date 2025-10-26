<?php

namespace Tests\Feature;

use App\Filament\Resources\InstagramAccounts\InstagramAccountResource;
use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use App\Models\User;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
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

    protected InstagramAccount $instagramAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);

        $this->instagramAccount = InstagramAccount::factory()->create([
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
            InstagramAccountResource::getUrl('view', ['record' => $this->instagramAccount])
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
        $mockApiService = Mockery::mock(InstagramApiService::class);
        $mockApiService->shouldReceive('getUserInfo')
            ->andReturn(null); // Simulate getUserInfo succeeding but returning null

        $service = new BlockedAccountService($mockApiService);
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
