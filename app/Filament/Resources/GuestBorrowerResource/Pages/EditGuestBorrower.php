<?php

namespace App\Filament\Resources\GuestBorrowerResource\Pages;

use App\Filament\Resources\GuestBorrowerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuestBorrower extends EditRecord
{
    protected static string $resource = GuestBorrowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
