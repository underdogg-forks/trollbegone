<?php

namespace App\Filament\Resources\InstagramAccounts\Pages;

use App\Filament\Resources\InstagramAccounts\InstagramAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstagramAccount extends EditRecord
{
    protected static string $resource = InstagramAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
