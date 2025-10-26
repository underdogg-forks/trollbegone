<?php

namespace App\Filament\Resources\BlockedAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use App\Models\InstagramAccount;

class BlockedAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('instagram_account_id')
                    ->label('Instagram Account')
                    ->relationship('instagramAccount', 'username')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('blocked_username')
                    ->required()
                    ->maxLength(255),
                TextInput::make('blocked_instagram_id')
                    ->maxLength(255),
                Textarea::make('reason')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Textarea::make('comment_text')
                    ->label('Comment that triggered the block')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
