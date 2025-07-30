<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Inventory Items';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Basic Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->nullable(),
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => request()->query('category_id'))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->columnSpanFull(),
                                    ]),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'available' => 'Available',
                                        'borrowed' => 'Borrowed',
                                        'under_repair' => 'Under Repair',
                                        'lost' => 'Lost',
                                    ])
                                    ->default('available')
                                    ->required(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Item Details')
                            ->schema([
                                Forms\Components\TextInput::make('serial_number')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('asset_tag')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Forms\Components\DatePicker::make('purchase_date'),
                                Forms\Components\TextInput::make('purchase_cost')
                                    ->label('Purchase Cost ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),
                                Forms\Components\DatePicker::make('warranty_expiry')
                                    ->label('Warranty Expiry Date'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Media')
                            ->schema([
                                Forms\Components\FileUpload::make('thumbnail')
                                    ->label('Item Image')
                                    ->image()
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('300')
                                    ->imageResizeTargetHeight('300')
                                    ->directory('thumbnails'),
                            ]),

                        Forms\Components\Section::make('Additional Information')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->rows(4),
                                Forms\Components\KeyValue::make('custom_attributes')
                                    ->keyLabel('Attribute')
                                    ->valueLabel('Value')
                                    ->addActionLabel('Add Attribute')
                                    ->reorderable()
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns([
                'sm' => 3,
                'lg' => 3,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Image')
                    ->defaultImageUrl(url('/storage/thumbnails/default.png'))
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('asset_tag')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'borrowed',
                        'danger' => 'lost',
                        'gray' => 'under_repair',
                    ]),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('purchase_cost')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('warranty_expiry')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'borrowed' => 'Borrowed',
                        'under_repair' => 'Under Repair',
                        'lost' => 'Lost',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_loans')
                    ->label('View Loan')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(function (Item $record) {
                        // Get the current active loan for this item
                        $currentLoan = $record->getCurrentLoan();

                        if ($currentLoan) {
                            return '/admin/loans/' . $currentLoan->id;
                        }

                        // If no current loan found, try to find any loan for this item
                        $anyLoan = $record->loans()
                            ->latest('loans.created_at')
                            ->first();

                        return $anyLoan ? '/admin/loans/' . $anyLoan->id : '#';
                    })
                    ->visible(function (Item $record) {
                        // Show for items that are borrowed, pending, or overdue (either by status or by active loans)
                        return in_array($record->status, ['borrowed']) || $record->isCurrentlyLoaned();
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('view_all_loans')
                    ->label('View All Loans')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->modalHeading(fn(Item $record) => "All Loans for {$record->name}")
                    ->modalContent(function (Item $record) {
                        $loans = $record->loans()->with(['borrower', 'department'])->orderBy('created_at', 'desc')->get();

                        if ($loans->isEmpty()) {
                            return view('components.empty-state', [
                                'title' => 'No Loans Found',
                                'description' => 'This item has not been loaned out yet.',
                                'icon' => 'heroicon-o-clipboard-document-list'
                            ]);
                        }

                        return view('components.item-loans-modal', [
                            'item' => $record,
                            'loans' => $loans
                        ]);
                    })
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(function (Item $record) {
                        // Show for any item with loan history
                        return $record->loans()->exists();
                    }),
                Tables\Actions\Action::make('loan')
                    ->label('Create Loan')
                    ->icon('heroicon-o-paper-clip')
                    ->color('success')
                    ->url(fn(Item $record) => route('loan.item', $record))
                    ->visible(fn(Item $record) => $record->status === 'available'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('changeStatus')
                        ->label('Change Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'available' => 'Available',
                                    'under_repair' => 'Under Repair',
                                    'lost' => 'Lost',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $records, array $data): void {
                            foreach ($records as $record) {
                                // Don't update borrowed items through bulk action
                                if ($record->status !== 'borrowed') {
                                    $record->update([
                                        'status' => $data['status'],
                                    ]);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LoansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view' => Pages\ViewItem::route('/{record}'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
