<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanItemResource\Pages;
use App\Helpers\ConditionTags;
use App\Models\LoanItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoanItemResource extends Resource
{
    protected static ?string $model = LoanItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Loan Management';

    protected static ?string $navigationLabel = 'Loan Items';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('loan_id')
                    ->relationship('loan', 'loan_number')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('item_id')
                    ->relationship('item', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Forms\Components\TagsInput::make('serial_numbers')
                    ->placeholder('Enter serial numbers'),
                Forms\Components\Textarea::make('condition_before')
                    ->label('Condition Before Loan')
                    ->placeholder('Describe the condition of the item before it was loaned'),
                Forms\Components\Select::make('condition_tags')
                    ->label('Condition Tags')
                    ->multiple()
                    ->searchable()
                    ->options(ConditionTags::grouped())
                    ->placeholder('Select condition tags')
                    ->helperText('Tags describing the condition of the item when returned'),
                Forms\Components\Textarea::make('return_notes')
                    ->label('Return Notes')
                    ->placeholder('Additional notes about the return'),
                Forms\Components\TextInput::make('returned_by')
                    ->label('Returned By')
                    ->placeholder('Name of person who processed the return'),
                Forms\Components\Select::make('status')
                    ->options([
                        'loaned' => 'Loaned',
                        'returned' => 'Returned',
                        'damaged' => 'Damaged',
                        'lost' => 'Lost',
                    ])
                    ->default('loaned')
                    ->required(),
                Forms\Components\DateTimePicker::make('returned_at')
                    ->label('Returned At'),
                Forms\Components\DateTimePicker::make('condition_assessed_at')
                    ->label('Condition Assessed At'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.loan_number')
                    ->label('Loan Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serial_numbers')
                    ->label('Serial Numbers')
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->limit(30),
                Tables\Columns\TextColumn::make('condition_tags')
                    ->label('Condition Tags')
                    ->formatStateUsing(fn($state) => ConditionTags::formatForDisplay($state))
                    ->visible(fn($record) => $record && $record->condition_tags && !empty($record->condition_tags)),
                Tables\Columns\TextColumn::make('return_notes')
                    ->label('Return Notes')
                    ->limit(50)
                    ->tooltip(fn(string $state): string => $state)
                    ->visible(fn($record) => $record && filled($record->return_notes)),
                Tables\Columns\TextColumn::make('returned_by')
                    ->label('Returned By')
                    ->visible(fn($record) => $record && filled($record->returned_by)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'loaned' => 'warning',
                        'returned' => 'success',
                        'damaged' => 'danger',
                        'lost' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('returned_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('condition_assessed_at')
                    ->dateTime()
                    ->sortable()
                    ->visible(fn($record) => $record && $record->condition_assessed_at),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'loaned' => 'Loaned',
                        'returned' => 'Returned',
                        'damaged' => 'Damaged',
                        'lost' => 'Lost',
                    ]),
                Tables\Filters\SelectFilter::make('loan_id')
                    ->relationship('loan', 'loan_number')
                    ->label('Loan'),
                Tables\Filters\SelectFilter::make('item_id')
                    ->relationship('item', 'name')
                    ->label('Item'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanItems::route('/'),
            'create' => Pages\CreateLoanItem::route('/create'),
            'view' => Pages\ViewLoanItem::route('/{record}'),
            'edit' => Pages\EditLoanItem::route('/{record}/edit'),
        ];
    }
}
