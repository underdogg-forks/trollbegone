<?php

namespace App\Filament\Resources\BlockedAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class BlockedAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instagramAccount.username')
                    ->label('Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('blocked_username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('blocked_instagram_id')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('reason')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('comment_text')
                    ->label('Comment')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Blocked At')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('instagram_account_id')
                    ->label('Instagram Account')
                    ->relationship('instagramAccount', 'username'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
