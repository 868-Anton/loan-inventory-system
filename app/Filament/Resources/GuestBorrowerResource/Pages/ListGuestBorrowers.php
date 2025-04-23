<?php

namespace App\Filament\Resources\GuestBorrowerResource\Pages;

use App\Filament\Resources\GuestBorrowerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuestBorrowers extends ListRecords
{
    protected static string $resource = GuestBorrowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
