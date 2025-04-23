<?php

namespace App\Filament\Resources\GuestBorrowerResource\RelationManagers;

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
        Forms\Components\DatePicker::make('loan_date')
          ->required(),
        Forms\Components\DatePicker::make('due_date')
          ->required(),
        Forms\Components\Select::make('status')
          ->options([
            'pending' => 'Pending',
            'active' => 'Active',
            'overdue' => 'Overdue',
            'returned' => 'Returned',
            'canceled' => 'Canceled',
          ]),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('loan_number')
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
          ->toggleable(),
        Tables\Columns\BadgeColumn::make('status')
          ->colors([
            'primary' => 'pending',
            'success' => 'active',
            'danger' => 'overdue',
            'info' => 'returned',
            'warning' => 'canceled',
          ]),
        Tables\Columns\TextColumn::make('items_count')
          ->label('Items')
          ->getStateUsing(fn($record) => $record->items->count())
          ->sortable(),
      ])
      ->filters([
        //
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->url(fn() => route('filament.admin.resources.loans.create')),
      ])
      ->actions([
        Tables\Actions\Action::make('view')
          ->url(fn($record) => route('filament.admin.resources.loans.view', $record))
          ->icon('heroicon-o-eye'),
        Tables\Actions\Action::make('print_voucher')
          ->label('Print Voucher')
          ->url(fn($record) => route('loans.voucher', $record))
          ->openUrlInNewTab()
          ->icon('heroicon-o-printer')
          ->visible(fn($record) => $record->voucher_path !== null),
      ])
      ->bulkActions([
        //
      ]);
  }
}
