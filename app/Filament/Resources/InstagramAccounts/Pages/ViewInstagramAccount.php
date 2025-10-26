<?php

namespace App\Filament\Resources\InstagramAccounts\Pages;

use App\Filament\Resources\InstagramAccounts\InstagramAccountResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInstagramAccount extends ViewRecord
{
    protected static string $resource = InstagramAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->using(function ($record, array $data) {
                    $record->update($data);
                    return $record;
                }),
            DeleteAction::make(),
        ];
    }
}
