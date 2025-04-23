<?php

namespace App\Filament\Resources\GuestBorrowerResource\Pages;

use App\Filament\Resources\GuestBorrowerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGuestBorrower extends CreateRecord
{
    protected static string $resource = GuestBorrowerResource::class;
}
