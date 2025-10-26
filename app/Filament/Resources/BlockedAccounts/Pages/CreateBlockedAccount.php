<?php

namespace App\Filament\Resources\BlockedAccounts\Pages;

use App\Filament\Resources\BlockedAccounts\BlockedAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlockedAccount extends CreateRecord
{
    protected static string $resource = BlockedAccountResource::class;
}
