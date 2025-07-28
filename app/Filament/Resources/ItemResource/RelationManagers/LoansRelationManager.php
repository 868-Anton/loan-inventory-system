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

  protected static ?string $title = 'Loan History';

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
          ->getStateUsing(function ($record) {
            $borrowerName = $record->getBorrowerName();

            // Add borrower type and info for guest borrowers
            if ($record->borrower_type === 'App\\Models\\GuestBorrower') {
              $info = [];

              if ($record->borrower && $record->borrower->email) {
                $info[] = $record->borrower->email;
              }

              if ($record->borrower && $record->borrower->id_number) {
                $info[] = "ID: " . $record->borrower->id_number;
              }

              if (!empty($info)) {
                $borrowerName .= ' (' . implode(', ', $info) . ')';
              }

              $borrowerName .= ' [Guest]';
            }

            return $borrowerName;
          })
          ->searchable(),
        Tables\Columns\TextColumn::make('loan_date')
          ->date()
          ->sortable(),
        Tables\Columns\TextColumn::make('due_date')
          ->date()
          ->sortable(),
        Tables\Columns\TextColumn::make('return_date')
          ->date()
          ->sortable()
          ->placeholder('Not returned'),
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
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('pivot.status')
          ->label('Item Status')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'loaned' => 'warning',
            'returned' => 'success',
            'damaged' => 'danger',
            'lost' => 'danger',
            default => 'gray',
          }),
        Tables\Columns\TextColumn::make('pivot.condition_before')
          ->label('Condition Before')
          ->words(10)
          ->tooltip(fn($record) => $record->pivot->condition_before ?? ''),
        Tables\Columns\TextColumn::make('pivot.condition_after')
          ->label('Condition After')
          ->words(10)
          ->tooltip(fn($record) => $record->pivot->condition_after ?? '')
          ->placeholder('Not recorded'),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('loan_status')
          ->label('Loan Type')
          ->options([
            'active' => 'Active Loans',
            'past' => 'Past Loans',
          ])
          ->query(function (Builder $query, array $data) {
            if ($data['value'] === 'active') {
              $query->whereIn('loans.status', ['active', 'pending', 'overdue']);
            } elseif ($data['value'] === 'past') {
              $query->whereIn('loans.status', ['returned', 'canceled']);
            }
          })
          ->default('active'),
        Tables\Filters\SelectFilter::make('status')
          ->options([
            'pending' => 'Pending',
            'active' => 'Active',
            'overdue' => 'Overdue',
            'returned' => 'Returned',
            'canceled' => 'Canceled',
          ])
          ->query(function (Builder $query, array $data) {
            if (isset($data['value'])) {
              $query->where('loans.status', $data['value']);
            }
          }),
      ])
      ->headerActions([
        // No header actions for relation
      ])
      ->actions([
        Tables\Actions\ViewAction::make()
          ->url(fn($record) => route('filament.admin.resources.loans.view', $record)),
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
      ->defaultSort('loan_date', 'desc')
      ->modifyQueryUsing(function (Builder $query) {
        // Always specify which table the status column belongs to
        return $query->select('loans.*');
      });
  }
}
