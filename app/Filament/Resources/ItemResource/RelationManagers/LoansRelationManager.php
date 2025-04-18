<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoansRelationManager extends RelationManager
{
  protected static string $relationship = 'loans';

  protected static ?string $recordTitleAttribute = 'loan_number';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('loan_number')
          ->required()
          ->maxLength(255),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('loan_number')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('borrower')
          ->label('Borrower')
          ->getStateUsing(fn($record) => $record->getBorrowerName())
          ->searchable(),
        Tables\Columns\TextColumn::make('loan_date')
          ->date()
          ->sortable(),
        Tables\Columns\TextColumn::make('due_date')
          ->date()
          ->sortable(),
        Tables\Columns\TextColumn::make('return_date')
          ->date()
          ->sortable(),
        Tables\Columns\BadgeColumn::make('status')
          ->colors([
            'primary' => 'pending',
            'success' => 'active',
            'danger' => 'overdue',
            'info' => 'returned',
            'warning' => 'canceled',
          ]),
        Tables\Columns\TextColumn::make('pivot.quantity')
          ->label('Quantity')
          ->sortable(),
        Tables\Columns\TextColumn::make('pivot.condition_before')
          ->label('Condition Before')
          ->words(10)
          ->tooltip(fn($record) => $record->pivot->condition_before ?? ''),
        Tables\Columns\TextColumn::make('pivot.condition_after')
          ->label('Condition After')
          ->words(10)
          ->tooltip(fn($record) => $record->pivot->condition_after ?? ''),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('status')
          ->options([
            'pending' => 'Pending',
            'active' => 'Active',
            'overdue' => 'Overdue',
            'returned' => 'Returned',
            'canceled' => 'Canceled',
          ]),
        Tables\Filters\Filter::make('current_loans')
          ->label('Current Loans')
          ->query(fn(Builder $query) => $query->whereNull('return_date')),
      ])
      ->headerActions([
        // No header actions for relation
      ])
      ->actions([
        Tables\Actions\ViewAction::make()
          ->url(fn($record) => route('filament.admin.resources.loans.edit', $record)),
        Tables\Actions\Action::make('printVoucher')
          ->label('Print Voucher')
          ->icon('heroicon-o-printer')
          ->url(fn($record) => route('loans.voucher', $record))
          ->openUrlInNewTab()
          ->color('gray')
          ->visible(fn($record) => $record->voucher_path !== null),
      ])
      ->bulkActions([
        // No bulk actions needed for this relation
      ])
      ->defaultSort('loan_date', 'desc');
  }
}
