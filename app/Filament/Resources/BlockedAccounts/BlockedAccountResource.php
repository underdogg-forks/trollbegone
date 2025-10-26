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

class BlockedAccountResource extends Resource
{
    protected static ?string $model = BlockedAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $navigationLabel = 'Blocked Accounts';

    protected static ?string $modelLabel = 'Blocked Account';

    public static function form(Schema $schema): Schema
    {
        return BlockedAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlockedAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlockedAccounts::route('/'),
            'create' => CreateBlockedAccount::route('/create'),
            'edit' => EditBlockedAccount::route('/{record}/edit'),
        ];
    }
}
