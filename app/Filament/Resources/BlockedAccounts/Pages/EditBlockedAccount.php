<?php

namespace App\Filament\Resources\BlockedAccounts\Pages;

use App\Filament\Resources\BlockedAccounts\BlockedAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlockedAccount extends EditRecord
{
    protected static string $resource = BlockedAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
