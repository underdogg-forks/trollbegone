<?php

namespace App\Filament\Resources\InstagramAccounts\Pages;

use App\Filament\Resources\InstagramAccounts\InstagramAccountResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstagramAccounts extends ListRecords
{
    protected static string $resource = InstagramAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect_instagram')
                ->label('Connect Instagram Account')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(route('instagram.oauth.redirect'))
                ->openUrlInNewTab(false),
            CreateAction::make(),
        ];
    }
}
