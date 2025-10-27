<?php

namespace Tests\Feature;

use App\Filament\Resources\InstagramAccounts\InstagramAccountResource;
use App\Filament\Resources\InstagramAccounts\Pages\ListInstagramAccounts;
use App\Filament\Resources\InstagramAccounts\Pages\ViewInstagramAccount;
use App\Models\InstagramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test Filament CRUD operations for InstagramAccount resource.
 *
 * Tests verify modal-based create, edit, and delete operations.
 */
class InstagramAccountResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);
    }

    #[Test]
    public function it_can_render_instagram_accounts_list_page(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // User and authentication set up in setUp()
        /** #endregion */

        /** #region Act */
        $response = $this->get(InstagramAccountResource::getUrl('index'));
        /** #endregion */

        /** #region Assert */
        $response->assertSuccessful();
        /** #endregion */
    }

    #[Test]
    public function it_can_list_instagram_accounts(): void
    {
        /** #region Arrange */
        $accounts = InstagramAccount::factory()->count(3)->create();
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ListInstagramAccounts::class);
        /** #endregion */

        /** #region Assert */
        $component->assertCanSeeTableRecords($accounts);
        /** #endregion */
    }

    #[Test]
    public function it_can_create_instagram_account_via_modal(): void
    {
        /** #region Arrange */
        $newData = [
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token_abc123',
            'is_active' => true,
        ];
        /** #endregion */

        /** #region Act */
        Livewire::test(ListInstagramAccounts::class)
            ->callAction('create', data: $newData);
        /** #endregion */

        /** #region Assert */
        $this->assertDatabaseHas('instagram_accounts', [
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'is_active' => true,
        ]);
        /** #endregion */
    }

    #[Test]
    public function it_can_validate_instagram_account_username_is_required(): void
    {
        /** #region Arrange */
        $invalidData = [
            'instagram_id' => '123456789',
        ];
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ListInstagramAccounts::class)
            ->callAction('create', data: $invalidData);
        /** #endregion */

        /** #region Assert */
        $component->assertHasActionErrors(['username' => 'required']);
        /** #endregion */
    }

    #[Test]
    public function it_can_validate_instagram_account_username_is_unique(): void
    {
        /** #region Arrange */
        $existingAccount = InstagramAccount::factory()->create([
            'username' => 'existing_account',
        ]);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ListInstagramAccounts::class)
            ->callAction('create', data: [
                'username' => 'existing_account',
            ]);
        /** #endregion */

        /** #region Assert */
        $component->assertHasActionErrors(['username' => 'unique']);
        /** #endregion */
    }

    #[Test]
    public function it_can_edit_instagram_account_via_modal(): void
    {
        /** #region Arrange */
        $account = InstagramAccount::factory()->create([
            'username' => 'original_username',
            'is_active' => true,
        ]);
        /** #endregion */

        /** #region Act */
        Livewire::test(ViewInstagramAccount::class, ['record' => $account->id])
            ->callAction('edit', data: [
                'username' => 'updated_username',
                'is_active' => false,
            ]);
        /** #endregion */

        /** #region Assert */
        $this->assertDatabaseHas('instagram_accounts', [
            'id' => $account->id,
            'username' => 'updated_username',
            'is_active' => false,
        ]);
        /** #endregion */
    }

    #[Test]
    public function it_can_delete_instagram_account(): void
    {
        /** #region Arrange */
        $account = InstagramAccount::factory()->create();
        /** #endregion */

        /** #region Act */
        Livewire::test(ViewInstagramAccount::class, ['record' => $account->id])
            ->callAction('delete');
        /** #endregion */

        /** #region Assert */
        $this->assertDatabaseMissing('instagram_accounts', [
            'id' => $account->id,
        ]);
        /** #endregion */
    }

    #[Test]
    public function it_can_bulk_delete_instagram_accounts(): void
    {
        /** #region Arrange */
        $accounts = InstagramAccount::factory()->count(3)->create();
        /** #endregion */

        /** #region Act */
        Livewire::test(ListInstagramAccounts::class)
            ->callTableBulkAction('delete', $accounts);
        /** #endregion */

        /** #region Assert */
        foreach ($accounts as $account) {
            $this->assertDatabaseMissing('instagram_accounts', [
                'id' => $account->id,
            ]);
        }
        /** #endregion */
    }

    #[Test]
    public function it_instagram_accounts_table_displays_correct_columns(): void
    {
        /** #region Arrange */
        $account = InstagramAccount::factory()->create([
            'username' => 'test_user',
            'instagram_id' => '987654321',
            'is_active' => true,
        ]);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ListInstagramAccounts::class);
        /** #endregion */

        /** #region Assert */
        $component->assertCanSeeTableRecords([$account])
            ->assertTableColumnExists('username')
            ->assertTableColumnExists('instagram_id')
            ->assertTableColumnExists('is_active')
            ->assertTableColumnExists('blockedAccounts_count');
        /** #endregion */
    }

    #[Test]
    public function it_can_search_instagram_accounts_by_username(): void
    {
        /** #region Arrange */
        $account1 = InstagramAccount::factory()->create(['username' => 'searchable_account']);
        $account2 = InstagramAccount::factory()->create(['username' => 'other_account']);
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ListInstagramAccounts::class)
            ->searchTable('searchable');
        /** #endregion */

        /** #region Assert */
        $component->assertCanSeeTableRecords([$account1])
            ->assertCanNotSeeTableRecords([$account2]);
        /** #endregion */
    }

    #[Test]
    public function it_instagram_accounts_are_sorted_by_default(): void
    {
        /** #region Arrange */
        $accounts = InstagramAccount::factory()->count(3)->create();
        /** #endregion */

        /** #region Act */
        $component = Livewire::test(ListInstagramAccounts::class);
        /** #endregion */

        /** #region Assert */
        $component->assertCanSeeTableRecords($accounts, inOrder: true);
        /** #endregion */
    }
}
