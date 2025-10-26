<?php

namespace App\Filament\Resources\BlockedAccounts;

use App\Filament\Resources\BlockedAccounts\Pages\CreateBlockedAccount;
use App\Filament\Resources\BlockedAccounts\Pages\EditBlockedAccount;
use App\Filament\Resources\BlockedAccounts\Pages\ListBlockedAccounts;
use App\Filament\Resources\BlockedAccounts\Schemas\BlockedAccountForm;
use App\Filament\Resources\BlockedAccounts\Tables\BlockedAccountsTable;
use App\Models\BlockedAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Blocked Account Resource
 *
 * Filament admin resource for managing blocked Instagram accounts. Allows
 * viewing, creating, editing, and deleting blocked account records.
 */
class BlockedAccountResource extends Resource
{
    protected static ?string $model = BlockedAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $navigationLabel = 'Blocked Accounts';

    /**
     * The singular model label.
     *
     * @var string|null
     */
    protected static ?string $modelLabel = 'Blocked Account';

    /**
     * Define the form schema for creating and editing blocked accounts.
     */
    public static function form(Schema $schema): Schema
    {
        return BlockedAccountForm::configure($schema);
    }

    /**
     * Define the table schema for listing blocked accounts.
     */
    public static function table(Table $table): Table
    {
        return BlockedAccountsTable::configure($table);
    }

    /**
     * Get the relations available on the resource.
     *
     * @return array<string, string>
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Get the pages available for this resource.
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListBlockedAccounts::route('/'),
            'create' => CreateBlockedAccount::route('/create'),
            'edit' => EditBlockedAccount::route('/{record}/edit'),
        ];
    }
}
