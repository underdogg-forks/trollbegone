<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Accounts\Pages\ViewAccount;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test Filament CRUD operations for Account resource.
 *
 * Tests verify modal-based create, edit, and delete operations.
 */
class AccountResourceTest extends TestCase
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
        /* Arrange */

        /* Act */
        $response = $this->get(AccountResource::getUrl('index'));

        /* Assert */
        $response->assertSuccessful();
    }

    #[Test]
    public function it_can_list_instagram_accounts(): void
    {
        /* Arrange */
        $accounts = Account::factory()->count(3)->create([
            'user_id' => $this->adminUser->id,
        ]);

        /* Act */
        $component = Livewire::test(ListAccounts::class);

        /* Assert */
        $component->assertCanSeeTableRecords($accounts);
    }

    #[Test]
    public function it_can_create_instagram_account_via_modal(): void
    {
        /* Arrange */
        $newData = [
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'access_token' => 'test_token_abc123',
            'is_active' => true,
        ];

        /* Act */
        Livewire::test(ListAccounts::class)
            ->callAction('create', data: $newData);

        /* Assert */
        $this->assertDatabaseHas('instagram_accounts', [
            'username' => 'test_account',
            'instagram_id' => '123456789',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_validate_instagram_account_username_is_required(): void
    {
        /* Arrange */
        $invalidData = [
            'instagram_id' => '123456789',
        ];

        /* Act */
        $component = Livewire::test(ListAccounts::class)
            ->callAction('create', data: $invalidData);

        /* Assert */
        $component->assertHasActionErrors(['username' => 'required']);
    }

    #[Test]
    public function it_can_validate_instagram_account_username_is_unique(): void
    {
        /* Arrange */
        Account::factory()->create([
            'username' => 'existing_account',
            'user_id' => $this->adminUser->id,
        ]);

        /* Act */
        $component = Livewire::test(ListAccounts::class)
            ->callAction('create', data: [
                'username' => 'existing_account',
            ]);

        /* Assert */
        $component->assertHasActionErrors(['username' => 'unique']);
    }

    #[Test]
    public function it_can_edit_instagram_account_via_modal(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'original_username',
            'is_active' => true,
            'user_id' => $this->adminUser->id,
        ]);

        /* Act */
        Livewire::test(ViewAccount::class, ['record' => $account->id])
            ->callAction('edit', data: [
                'username' => 'updated_username',
                'is_active' => false,
            ]);

        /* Assert */
        $this->assertDatabaseHas('instagram_accounts', [
            'id' => $account->id,
            'username' => 'updated_username',
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_can_delete_instagram_account(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['user_id' => $this->adminUser->id]);

        /* Act */
        Livewire::test(ViewAccount::class, ['record' => $account->id])
            ->callAction('delete');

        /* Assert */
        $this->assertDatabaseMissing('instagram_accounts', [
            'id' => $account->id,
        ]);
    }

    #[Test]
    public function it_can_bulk_delete_instagram_accounts(): void
    {
        /* Arrange */
        $accounts = Account::factory()->count(3)->create(['user_id' => $this->adminUser->id]);

        /* Act */
        Livewire::test(ListAccounts::class)
            ->callTableBulkAction('delete', $accounts);

        /* Assert */
        foreach ($accounts as $account) {
            $this->assertDatabaseMissing('instagram_accounts', [
                'id' => $account->id,
            ]);
        }
    }

    #[Test]
    public function it_instagram_accounts_table_displays_correct_columns(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_user',
            'instagram_id' => '987654321',
            'is_active' => true,
            'user_id' => $this->adminUser->id,
        ]);

        /* Act */
        $component = Livewire::test(ListAccounts::class);

        /* Assert */
        $component->assertCanSeeTableRecords([$account])
            ->assertTableColumnExists('username')
            ->assertTableColumnExists('instagram_id')
            ->assertTableColumnExists('is_active')
            ->assertTableColumnExists('blockedAccounts_count');
    }

    #[Test]
    public function it_can_search_instagram_accounts_by_username(): void
    {
        /* Arrange */
        $account1 = Account::factory()->create([
            'username' => 'searchable_account',
            'user_id' => $this->adminUser->id,
        ]);
        $account2 = Account::factory()->create([
            'username' => 'other_account',
            'user_id' => $this->adminUser->id,
        ]);

        /* Act */
        $component = Livewire::test(ListAccounts::class)
            ->searchTable('searchable');

        /* Assert */
        $component->assertCanSeeTableRecords([$account1])
            ->assertCanNotSeeTableRecords([$account2]);
    }

    #[Test]
    public function it_instagram_accounts_are_sorted_by_default(): void
    {
        /* Arrange */
        $accounts = Account::factory()->count(3)->create(['user_id' => $this->adminUser->id]);

        /* Act */
        $component = Livewire::test(ListAccounts::class);

        /* Assert */
        $component->assertCanSeeTableRecords($accounts, inOrder: true);
    }
}
