<?php

namespace App\Filament\Resources\InstagramAccounts;

use App\Filament\Resources\InstagramAccounts\Pages\CreateInstagramAccount;
use App\Filament\Resources\InstagramAccounts\Pages\EditInstagramAccount;
use App\Filament\Resources\InstagramAccounts\Pages\ListInstagramAccounts;
use App\Filament\Resources\InstagramAccounts\Schemas\InstagramAccountForm;
use App\Filament\Resources\InstagramAccounts\Tables\InstagramAccountsTable;
use App\Models\InstagramAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstagramAccountResource extends Resource
{
    protected static ?string $model = InstagramAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Instagram Accounts';

    protected static ?string $modelLabel = 'Instagram Account';

    protected static ?string $recordTitleAttribute = 'username';

    public static function form(Schema $schema): Schema
    {
        return InstagramAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstagramAccountsTable::configure($table);
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
            'index' => ListInstagramAccounts::route('/'),
            'create' => CreateInstagramAccount::route('/create'),
            'edit' => EditInstagramAccount::route('/{record}/edit'),
            'stories' => Pages\ViewStories::route('/{record}/stories'),
        ];
    }
}
