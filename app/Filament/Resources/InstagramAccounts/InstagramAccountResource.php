<?php

namespace App\Filament\Resources\InstagramAccounts;

use App\Filament\Resources\InstagramAccounts\Pages\ListInstagramAccounts;
use App\Filament\Resources\InstagramAccounts\Schemas\InstagramAccountForm;
use App\Filament\Resources\InstagramAccounts\Tables\InstagramAccountsTable;
use App\Models\InstagramAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Instagram Account Resource
 *
 * Filament admin resource for managing Instagram Business accounts. Provides
 * CRUD operations and a custom "View Stories" action for browsing stories and
 * their comments.
 */
class InstagramAccountResource extends Resource
{
    protected static ?string $model = InstagramAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Instagram Accounts';

    /**
     * The singular model label.
     *
     * @var string|null
     */
    protected static ?string $modelLabel = 'Instagram Account';

    protected static ?string $recordTitleAttribute = 'username';

    /**
     * Define the form schema for creating and editing Instagram accounts.
     */
    public static function form(Schema $schema): Schema
    {
        return InstagramAccountForm::configure($schema);
    }

    /**
     * Define the table schema for listing Instagram accounts.
     */
    public static function table(Table $table): Table
    {
        return InstagramAccountsTable::configure($table);
    }

    /**
     * Get the relations available on the resource.
     *
     * @return array<string, string>
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\BlockedAccountsRelationManager::class,
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
            'index' => ListInstagramAccounts::route('/'),
            'view' => Pages\ViewInstagramAccount::route('/{record}'),
            'stories' => Pages\ViewStories::route('/{record}/stories'),
        ];
    }
}
