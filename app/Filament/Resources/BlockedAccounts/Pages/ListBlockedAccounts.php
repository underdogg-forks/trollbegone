<?php

namespace App\Filament\Resources\BlockedAccounts\Pages;

use App\Filament\Resources\BlockedAccounts\BlockedAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlockedAccounts extends ListRecords
{
    protected static string $resource = BlockedAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
