<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewLoan extends ViewRecord
{
  protected static string $resource = LoanResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
      Actions\Action::make('print_voucher')
        ->label('Print Voucher')
        ->url(fn() => route('loans.voucher', $this->record))
        ->openUrlInNewTab()
        ->icon('heroicon-o-printer')
        ->visible(fn() => $this->record->voucher_path !== null),
      Actions\Action::make('return')
        ->label('Return Loan')
        ->icon('heroicon-o-arrow-uturn-left')
        ->color('success')
        ->form([
          \Filament\Forms\Components\Textarea::make('notes')
            ->label('Return Notes')
            ->placeholder('Condition of returned items, missing parts, damage, etc.')
        ])
        ->action(function (array $data): void {
          $this->record->markAsReturned($data['notes'] ?? null);

          \Filament\Notifications\Notification::make()
            ->success()
            ->title('Loan Returned')
            ->body('All items have been marked as returned.')
            ->send();
        })
        ->requiresConfirmation()
        ->modalHeading('Return Loan')
        ->modalDescription('Mark this loan as returned? This will update the status of all borrowed items.')
        ->visible(fn() => $this->record->status !== 'returned' && $this->record->status !== 'canceled'),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Loan Information')
          ->schema([
            Infolists\Components\TextEntry::make('loan_number'),
            Infolists\Components\TextEntry::make('status')
              ->badge()
              ->color(fn(string $state): string => match ($state) {
                'pending' => 'gray',
                'active' => 'success',
                'overdue' => 'danger',
                'returned' => 'info',
                'canceled' => 'warning',
                default => 'gray',
              }),
            Infolists\Components\TextEntry::make('loan_date')
              ->date(),
            Infolists\Components\TextEntry::make('due_date')
              ->date(),
            Infolists\Components\TextEntry::make('return_date')
              ->date(),
            Infolists\Components\TextEntry::make('notes')
              ->columnSpanFull(),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Borrower Information')
          ->schema([
            Infolists\Components\TextEntry::make('borrower')
              ->label('Borrower')
              ->getStateUsing(function ($record): string {
                // If using the polymorphic relationship
                if ($record->borrower_type && $record->borrower_id) {
                  $borrowerName = $record->borrower?->name ?? 'Unknown';

                  // Show label for guest borrowers
                  if ($record->borrower_type === 'App\\Models\\GuestBorrower') {
                    $borrowerName .= ' [Guest]';
                  }

                  return $borrowerName;
                }

                // Legacy fallback
                return $record->getBorrowerName();
              }),
            Infolists\Components\TextEntry::make('department.name')
              ->label('Department'),
            Infolists\Components\TextEntry::make('borrower_email')
              ->label('Email')
              ->getStateUsing(fn($record) => $record->getBorrowerEmail()),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Borrowed Items')
          ->schema([
            Infolists\Components\RepeatableEntry::make('items')
              ->schema([
                Infolists\Components\TextEntry::make('name')
                  ->label('Item Name'),
                Infolists\Components\TextEntry::make('serial_number')
                  ->label('Serial Number'),
                Infolists\Components\TextEntry::make('pivot.deprecated_quantity')
                  ->label('Quantity'),
                Infolists\Components\TextEntry::make('pivot.condition_before')
                  ->label('Condition Before'),
                Infolists\Components\TextEntry::make('pivot.condition_after')
                  ->label('Condition After'),
                Infolists\Components\TextEntry::make('pivot.status')
                  ->label('Item Status')
                  ->badge()
                  ->color(fn(string $state): string => match (strtolower($state)) {
                    'loaned' => 'warning',
                    'returned' => 'success',
                    'damaged' => 'danger',
                    'lost' => 'danger',
                    default => 'gray',
                  }),
              ])
              ->columns(3),
          ]),

        Infolists\Components\Section::make('Documentation')
          ->schema([
            Infolists\Components\ImageEntry::make('signature')
              ->disk('public')
              ->label('Borrower Signature'),
            Infolists\Components\TextEntry::make('voucher_path')
              ->label('Voucher')
              ->formatStateUsing(fn($state) => $state ? 'Available' : 'Not available')
              ->url(fn($record) => $record->voucher_path ? route('loans.voucher', $record) : null)
              ->openUrlInNewTab()
              ->visible(fn($record) => $record->voucher_path !== null),
          ])
          ->columns(2),
      ]);
  }
}
