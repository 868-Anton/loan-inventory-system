<?php

namespace App\Filament\Resources\LoanItemResource\Pages;

use App\Filament\Resources\LoanItemResource;
use App\Helpers\ConditionTags;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewLoanItem extends ViewRecord
{
  protected static string $resource = LoanItemResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Loan Item Information')
          ->schema([
            Infolists\Components\TextEntry::make('loan.loan_number')
              ->label('Loan Number'),
            Infolists\Components\TextEntry::make('item.name')
              ->label('Item Name'),
            Infolists\Components\TextEntry::make('item.category.name')
              ->label('Category'),
            Infolists\Components\TextEntry::make('quantity')
              ->numeric(),
            Infolists\Components\TextEntry::make('serial_numbers')
              ->label('Serial Numbers')
              ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
            Infolists\Components\TextEntry::make('status')
              ->badge()
              ->color(fn(string $state): string => match (strtolower($state)) {
                'loaned' => 'warning',
                'returned' => 'success',
                'damaged' => 'danger',
                'lost' => 'danger',
                default => 'gray',
              }),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Condition Assessment')
          ->schema([
            Infolists\Components\TextEntry::make('condition_before')
              ->label('Condition Before Loan')
              ->visible(fn($record) => filled($record->condition_before)),
            Infolists\Components\TextEntry::make('condition_tags')
              ->label('Condition Tags')
              ->formatStateUsing(fn($state) => ConditionTags::formatForDisplay($state))
              ->visible(fn($record) => $record->condition_tags && !empty($record->condition_tags)),
            Infolists\Components\TextEntry::make('return_notes')
              ->label('Return Notes')
              ->visible(fn($record) => filled($record->return_notes)),
            Infolists\Components\TextEntry::make('returned_by')
              ->label('Returned By')
              ->visible(fn($record) => filled($record->returned_by)),
            Infolists\Components\TextEntry::make('condition_assessed_at')
              ->label('Condition Assessed At')
              ->dateTime()
              ->visible(fn($record) => $record->condition_assessed_at),
          ])
          ->columns(2)
          ->visible(fn($record) => $record->isReturned()),

        Infolists\Components\Section::make('Timestamps')
          ->schema([
            Infolists\Components\TextEntry::make('created_at')
              ->dateTime(),
            Infolists\Components\TextEntry::make('updated_at')
              ->dateTime(),
            Infolists\Components\TextEntry::make('returned_at')
              ->dateTime()
              ->visible(fn($record) => $record->returned_at),
          ])
          ->columns(3),
      ]);
  }
}
