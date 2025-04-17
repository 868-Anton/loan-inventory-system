<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'name'),
                Forms\Components\TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('color')
                    ->maxLength(255),
                Forms\Components\TextInput::make('custom_fields'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('color')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('All Items')
                    ->counts('items')
                    ->url(fn(Category $record): string => route('categories.items', $record))
                    ->badge()
                    ->color('success')
                    ->tooltip('Click to view all items in this category, regardless of status'),
                Tables\Columns\TextColumn::make('available_items_count')
                    ->label('Available Items')
                    ->state(function (Category $record): int {
                        return $record->items()
                            ->whereRaw('LOWER(status) = ?', ['available'])
                            ->count();
                    })
                    ->url(fn(Category $record): string => route('categories.items', ['category' => $record, 'filter' => 'available']))
                    ->badge()
                    ->color('warning') // Orange
                    ->alignCenter()
                    ->tooltip('Click to view available items in this category'),
                Tables\Columns\TextColumn::make('borrowed_items_count')
                    ->label('Borrowed Items')
                    ->state(function (Category $record): int {
                        // Count items in this category that are either:
                        // 1. Directly marked as "borrowed" in the items table
                        // 2. Currently part of an active loan
                        return $record->items()
                            ->where(function ($query) {
                                $query->whereRaw('LOWER(status) = ?', ['borrowed'])
                                    ->orWhereHas('loans', function ($loanQuery) {
                                        $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                                            ->whereRaw('LOWER(loan_items.status) = ?', ['loaned']);
                                    });
                            })
                            ->count();
                    })
                    ->url(fn(Category $record): string => route('categories.items', ['category' => $record, 'filter' => 'borrowed']))
                    ->badge()
                    ->color('danger')
                    ->alignCenter()
                    ->tooltip('Click to view borrowed items in this category'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->iconButton()
                    ->label('')
                    ->modalWidth('lg'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // Add row click behavior
            ->recordUrl(fn(Category $record): string => route('categories.items', $record));
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
