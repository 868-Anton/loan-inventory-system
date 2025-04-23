<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

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
        ->visible(fn() => $this->record->status === 'available' && !$this->record->isCurrentlyLoaned()),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Active Loans Summary')
          ->visible(fn() => $this->record->isCurrentlyLoaned())
          ->description(fn() => "This item currently has " . $this->record->borrowedQuantity() . " units on loan.")
          ->schema([
            Infolists\Components\TextEntry::make('current_loans')
              ->label('Current Borrowers')
              ->getStateUsing(function () {
                $activeLoans = $this->record->loans()
                  ->whereIn('loans.status', ['active', 'pending', 'overdue'])
                  ->whereRaw('LOWER(loan_items.status) = ?', ['loaned'])
                  ->get();

                // Format the output as a bullet list of borrowers and dates
                if ($activeLoans->isEmpty()) {
                  return 'None';
                }

                $borrowerList = [];
                foreach ($activeLoans as $loan) {
                  $info = $loan->getBorrowerName();
                  $info .= " - " . $loan->loan_number . " (";
                  $info .= $loan->pivot->quantity . " unit" . ($loan->pivot->quantity > 1 ? "s" : "") . ", ";
                  $info .= "Due: " . $loan->due_date->format('M d, Y') . ")";
                  $borrowerList[] = $info;
                }

                return implode("<br>", $borrowerList);
              })
              ->html(),
          ]),
      ]);
  }
}
