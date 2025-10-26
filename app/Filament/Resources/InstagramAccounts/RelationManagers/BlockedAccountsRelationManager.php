<?php

namespace App\Filament\Resources\InstagramAccounts\RelationManagers;

use App\Services\Instagram\BlockedAccountService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Blocked Accounts Relation Manager
 *
 * Manages the blocked accounts relationship for Instagram accounts.
 * This replaces the standalone BlockedAccountResource.
 */
class BlockedAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'blockedAccounts';

    protected static ?string $recordTitleAttribute = 'blocked_username';

    /**
     * Configure the table for the relation manager.
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('blocked_username')
                    ->searchable()
                    ->sortable()
                    ->label('Username'),
                TextColumn::make('blocked_instagram_id')
                    ->toggleable()
                    ->searchable()
                    ->label('Instagram ID'),
                TextColumn::make('reason')
                    ->limit(50)
                    ->toggleable()
                    ->label('Reason'),
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
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, RelationManager $livewire): Model {
                        $service = app(BlockedAccountService::class);

                        return $service->blockAccount(
                            $livewire->getOwnerRecord(),
                            $data['blocked_username'],
                            $data['reason'] ?? null,
                            $data['comment_text'] ?? null
                        );
                    })
                    ->form([
                        TextInput::make('blocked_username')
                            ->required()
                            ->maxLength(255)
                            ->label('Username to Block'),
                        Textarea::make('reason')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Reason for Blocking'),
                        Textarea::make('comment_text')
                            ->label('Comment that triggered the block')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        $record->update($data);

                        return $record;
                    })
                    ->form([
                        TextInput::make('blocked_username')
                            ->required()
                            ->maxLength(255)
                            ->label('Username'),
                        TextInput::make('blocked_instagram_id')
                            ->maxLength(255)
                            ->label('Instagram ID'),
                        Textarea::make('reason')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Reason'),
                        Textarea::make('comment_text')
                            ->label('Comment that triggered the block')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
