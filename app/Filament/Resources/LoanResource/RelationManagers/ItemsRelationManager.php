<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
  protected static string $relationship = 'items';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Select::make('item_id')
          ->relationship('item', 'name')
          ->required(),
        Forms\Components\Textarea::make('condition_before')
          ->placeholder('Item condition before loan'),
        Forms\Components\Textarea::make('condition_after')
          ->placeholder('Item condition after return'),
        Forms\Components\Select::make('status')
          ->options([
            'loaned' => 'Loaned',
            'returned' => 'Returned',
            'damaged' => 'Damaged',
            'lost' => 'Lost',
          ])
          ->default('loaned')
          ->required(),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable(),
        Tables\Columns\TextColumn::make('serial_number')
          ->searchable(),
        Tables\Columns\TextColumn::make('asset_tag')
          ->searchable(),
        Tables\Columns\TextColumn::make('category.name')
          ->sortable(),
        Tables\Columns\TextColumn::make('pivot.deprecated_quantity')
          ->label('Quantity'),
        Tables\Columns\TextColumn::make('pivot.status')
          ->label('Loan Status')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'loaned' => 'warning',
            'returned' => 'success',
            'damaged' => 'danger',
            'lost' => 'danger',
            default => 'gray',
          }),
        Tables\Columns\TextColumn::make('pivot.condition_before')
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('pivot.condition_after')
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        //
      ])
      ->headerActions([
        Tables\Actions\AttachAction::make()
          ->preloadRecordSelect()
          ->form(fn(Tables\Actions\AttachAction $action): array => [
            $action->getRecordSelect()
              ->label('Select Item')
              ->helperText('Only available items are shown'),
            Forms\Components\TextInput::make('deprecated_quantity')
              ->label('Quantity')
              ->numeric()
              ->default(1)
              ->minValue(1),
            Forms\Components\Textarea::make('condition_before')
              ->placeholder('Item condition before loan'),
            Forms\Components\Select::make('status')
              ->options([
                'loaned' => 'Loaned',
                'returned' => 'Returned',
                'damaged' => 'Damaged',
                'lost' => 'Lost',
              ])
              ->default('loaned')
              ->required(),
          ]),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DetachAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DetachBulkAction::make(),
        ]),
      ]);
  }
}
