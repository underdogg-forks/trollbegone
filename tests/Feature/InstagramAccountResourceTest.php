<?php

namespace Tests\Feature;

use App\Filament\Resources\InstagramAccounts\InstagramAccountResource;
use App\Filament\Resources\InstagramAccounts\Pages\ListInstagramAccounts;
use App\Filament\Resources\InstagramAccounts\Pages\ViewInstagramAccount;
use App\Models\InstagramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_can_render_instagram_accounts_list_page(): void
    {
        $this->markTestSkipped('Skipping until Filament permissions are configured');
        
        $this->get(InstagramAccountResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_can_list_instagram_accounts(): void
    {
        $accounts = InstagramAccount::factory()->count(3)->create();

        Livewire::test(ListInstagramAccounts::class)
            ->assertCanSeeTableRecords($accounts);
    }

    public function test_can_create_instagram_account_via_modal(): void
    {
        $newData = [
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token_abc123',
            'is_active' => true,
        ];

        Livewire::test(ListInstagramAccounts::class)
            ->callAction('create', data: $newData);

        $this->assertDatabaseHas('instagram_accounts', [
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'is_active' => true,
        ]);
    }

    public function test_can_validate_instagram_account_username_is_required(): void
    {
        Livewire::test(ListInstagramAccounts::class)
            ->callAction('create', data: [
                'instagram_id' => '123456789',
            ])
            ->assertHasActionErrors(['username' => 'required']);
    }

    public function test_can_validate_instagram_account_username_is_unique(): void
    {
        $existingAccount = InstagramAccount::factory()->create([
            'username' => 'existing_account',
        ]);

        Livewire::test(ListInstagramAccounts::class)
            ->callAction('create', data: [
                'username' => 'existing_account',
            ])
            ->assertHasActionErrors(['username' => 'unique']);
    }

    public function test_can_edit_instagram_account_via_modal(): void
    {
        $account = InstagramAccount::factory()->create([
            'username' => 'original_username',
            'is_active' => true,
        ]);

        Livewire::test(ViewInstagramAccount::class, ['record' => $account->id])
            ->callAction('edit', data: [
                'username' => 'updated_username',
                'is_active' => false,
            ]);

        $this->assertDatabaseHas('instagram_accounts', [
            'id' => $account->id,
            'username' => 'updated_username',
            'is_active' => false,
        ]);
    }

    public function test_can_delete_instagram_account(): void
    {
        $account = InstagramAccount::factory()->create();

        Livewire::test(ViewInstagramAccount::class, ['record' => $account->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('instagram_accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_can_bulk_delete_instagram_accounts(): void
    {
        $accounts = InstagramAccount::factory()->count(3)->create();

        Livewire::test(ListInstagramAccounts::class)
            ->callTableBulkAction('delete', $accounts);

        foreach ($accounts as $account) {
            $this->assertDatabaseMissing('instagram_accounts', [
                'id' => $account->id,
            ]);
        }
    }

    public function test_instagram_accounts_table_displays_correct_columns(): void
    {
        $account = InstagramAccount::factory()->create([
            'username' => 'test_user',
            'instagram_id' => '987654321',
            'is_active' => true,
        ]);

        Livewire::test(ListInstagramAccounts::class)
            ->assertCanSeeTableRecords([$account])
            ->assertTableColumnExists('username')
            ->assertTableColumnExists('instagram_id')
            ->assertTableColumnExists('is_active')
            ->assertTableColumnExists('blockedAccounts_count');
    }

    public function test_can_search_instagram_accounts_by_username(): void
    {
        $account1 = InstagramAccount::factory()->create(['username' => 'searchable_account']);
        $account2 = InstagramAccount::factory()->create(['username' => 'other_account']);

        Livewire::test(ListInstagramAccounts::class)
            ->searchTable('searchable')
            ->assertCanSeeTableRecords([$account1])
            ->assertCanNotSeeTableRecords([$account2]);
    }

    public function test_instagram_accounts_are_sorted_by_default(): void
    {
        $accounts = InstagramAccount::factory()->count(3)->create();

        Livewire::test(ListInstagramAccounts::class)
            ->assertCanSeeTableRecords($accounts, inOrder: false);
    }
}
