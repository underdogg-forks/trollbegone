<?php

namespace App\Filament\Resources\Accounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Account Form Schema
 *
 * Defines the form fields for creating and editing accounts in the
 * Filament admin panel.
 */
class AccountForm
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
                Toggle::make('is_active')
                    ->default(true),
                DateTimePicker::make('last_synced_at')
                    ->disabled()
                    ->helperText('Last time stories were fetched'),
            ]);
    }
}
