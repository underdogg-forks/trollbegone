<?php

namespace App\Filament\Resources\InstagramAccounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Instagram Account Form Schema
 *
 * Defines the form fields for creating and editing Instagram accounts in the
 * Filament admin panel.
 */
class InstagramAccountForm
{
    /**
     * Configure the form schema with all required fields.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('instagram_id')
                    ->maxLength(255),
                TextInput::make('access_token')
                    ->password()
                    ->maxLength(255)
                    ->helperText('Instagram Graph API access token'),
                Toggle::make('is_active')
                    ->default(true),
                DateTimePicker::make('last_synced_at')
                    ->disabled()
                    ->helperText('Last time stories were fetched'),
            ]);
    }
}
