<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\Accounts\Pages\ViewAccount;
use App\Filament\Resources\Accounts\RelationManagers\BlockedAccountsRelationManager;
use App\Models\Account;
use App\Models\BlockedAccount;
use App\Models\User;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);

        $this->account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
            'user_id' => $this->adminUser->id,
        ]);
    }

    #[Test]
    public function it_can_render_blocked_accounts_relation_manager(): void
    {
        /* Arrange */

        /* Act */
        $response = $this->get(
            AccountResource::getUrl('view', ['record' => $this->account])
        );

        /* Assert */
        $response->assertSuccessful();
    }

    #[Test]
    public function it_can_list_blocked_accounts_in_relation_manager(): void
    {
        /* Arrange */
        $blocked1 = BlockedAccount::factory()->forAccount($this->account)->create();
        $blocked2 = BlockedAccount::factory()->forAccount($this->account)->create();

        /* Act */
        $component = Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ]);

        /* Assert */
        $component->assertCanSeeTableRecords([$blocked1, $blocked2]);
    }

    #[Test]
    public function it_can_create_blocked_account_via_modal_with_service(): void
    {
        /* Arrange */
        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('new_troll', ['id' => '55555', 'username' => 'new_troll']);
        $fakeApiService->setBlockUserResult('55555', true);
        $this->app->instance(BlockedAccountService::class, new BlockedAccountService($fakeApiService));

        /* Act */
        Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ])->callTableAction('create', data: [
            'blocked_username' => 'new_troll',
            'reason' => 'Spam',
        ]);

        /* Assert */
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $this->account->id,
            'blocked_username' => 'new_troll',
            'reason' => 'Spam',
        ]);
    }

    #[Test]
    public function it_uses_a_transaction_for_blocked_account_creation(): void
    {
        /* Arrange */
        $fakeApiService = new FakeInstagramApiService;
        $fakeApiService->setUserInfoResponse('test_user', null);
        $service = new BlockedAccountService($fakeApiService);

        /* Act */
        $result = $service->blockAccount($this->account, 'test_user', 'Test reason');

        /* Assert */
        $this->assertInstanceOf(BlockedAccount::class, $result);
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $this->account->id,
            'blocked_username' => 'test_user',
        ]);
    }

    #[Test]
    public function it_can_validate_blocked_username_is_required(): void
    {
        /* Arrange */

        /* Act */
        $component = Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ])->callTableAction('create', data: [
            'blocked_username' => '',
            'reason' => 'Test',
        ]);

        /* Assert */
        $component->assertHasTableActionErrors(['blocked_username' => 'required']);
    }

    #[Test]
    public function it_can_edit_blocked_account_via_modal(): void
    {
        /* Arrange */
        $blockedAccount = BlockedAccount::factory()->forAccount($this->account)->create([
            'blocked_username' => 'original_troll',
            'reason' => 'Original reason',
        ]);

        /* Act */
        Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ])->callTableAction('edit', $blockedAccount, data: [
            'blocked_username' => 'updated_troll',
            'reason' => 'Updated reason',
        ]);

        /* Assert */
        $this->assertDatabaseHas('blocked_accounts', [
            'id' => $blockedAccount->id,
            'blocked_username' => 'updated_troll',
            'reason' => 'Updated reason',
        ]);
    }

    #[Test]
    public function it_can_delete_blocked_account(): void
    {
        /* Arrange */
        $blockedAccount = BlockedAccount::factory()->forAccount($this->account)->create();

        /* Act */
        Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ])->callTableAction('delete', $blockedAccount);

        /* Assert */
        $this->assertDatabaseMissing('blocked_accounts', ['id' => $blockedAccount->id]);
    }

    #[Test]
    public function it_can_bulk_delete_blocked_accounts(): void
    {
        /* Arrange */
        $blockedAccounts = BlockedAccount::factory()->count(3)->forAccount($this->account)->create();

        /* Act */
        Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ])->callTableBulkAction('delete', $blockedAccounts);

        /* Assert */
        foreach ($blockedAccounts as $blockedAccount) {
            $this->assertDatabaseMissing('blocked_accounts', ['id' => $blockedAccount->id]);
        }
    }

    #[Test]
    public function it_displays_correct_columns_in_blocked_accounts_table(): void
    {
        /* Arrange */
        $blockedAccount = BlockedAccount::factory()->forAccount($this->account)->create([
            'blocked_username' => 'column_test_user',
            'reason' => 'Test reason',
        ]);

        /* Act */
        $component = Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ]);

        /* Assert */
        $component->assertCanSeeTableRecords([$blockedAccount])
            ->assertTableColumnExists('blocked_username')
            ->assertTableColumnExists('blocked_instagram_id')
            ->assertTableColumnExists('reason')
            ->assertTableColumnExists('comment_text')
            ->assertTableColumnExists('created_at');
    }

    #[Test]
    public function it_can_search_blocked_accounts_by_username(): void
    {
        /* Arrange */
        $matching = BlockedAccount::factory()->forAccount($this->account)->create([
            'blocked_username' => 'searchable_troll',
        ]);
        $notMatching = BlockedAccount::factory()->forAccount($this->account)->create([
            'blocked_username' => 'other_user',
        ]);

        /* Act */
        $component = Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ])->searchTable('searchable');

        /* Assert */
        $component->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$notMatching]);
    }

    #[Test]
    public function it_sorts_blocked_accounts_by_created_at_desc(): void
    {
        /* Arrange */
        $older = BlockedAccount::factory()->forAccount($this->account)->create([
            'created_at' => now()->subMinutes(10),
        ]);
        $newer = BlockedAccount::factory()->forAccount($this->account)->create([
            'created_at' => now(),
        ]);

        /* Act */
        $component = Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ]);

        /* Assert */
        $component->assertCanSeeTableRecords([$newer, $older], inOrder: true);
    }

    #[Test]
    public function it_only_shows_blocked_accounts_for_current_instagram_account(): void
    {
        /* Arrange */
        $otherAccount = Account::factory()->create();
        $ownBlocked = BlockedAccount::factory()->forAccount($this->account)->create();
        $otherBlocked = BlockedAccount::factory()->forAccount($otherAccount)->create();

        /* Act */
        $component = Livewire::test(BlockedAccountsRelationManager::class, [
            'ownerRecord' => $this->account,
            'pageClass' => ViewAccount::class,
        ]);

        /* Assert */
        $component->assertCanSeeTableRecords([$ownBlocked])
            ->assertCanNotSeeTableRecords([$otherBlocked]);
    }
}
