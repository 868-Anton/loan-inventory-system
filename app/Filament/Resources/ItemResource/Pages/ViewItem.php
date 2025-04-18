<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewItem extends ViewRecord
{
  protected static string $resource = ItemResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
      Actions\Action::make('createLoan')
        ->label('Create Loan')
        ->icon('heroicon-o-paper-clip')
        ->color('success')
        ->url(fn() => route('loan.item', $this->record))
        ->visible(fn() => $this->record->status === 'available'),
    ];
  }
}
