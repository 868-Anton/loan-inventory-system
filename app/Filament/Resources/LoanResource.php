<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Item;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Loan Information')
                            ->schema([
                                Forms\Components\TextInput::make('loan_number')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->required(),
                                Forms\Components\Select::make('department_id')
                                    ->relationship('department', 'name'),
                                Forms\Components\DatePicker::make('loan_date')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        // Only set the due date if loan_date has a value and due_date is empty
                                        if ($state && !$get('due_date')) {
                                            $set('due_date', Carbon::parse($state)->addMonth()->format('Y-m-d'));
                                        }
                                    }),
                                Forms\Components\DatePicker::make('due_date')
                                    ->required()
                                    ->helperText('Defaults to 1 month after loan date if not specified'),
                                Forms\Components\DatePicker::make('return_date'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'active' => 'Active',
                                        'overdue' => 'Overdue',
                                        'returned' => 'Returned',
                                        'canceled' => 'Canceled',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                Forms\Components\Textarea::make('notes')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Borrower Information')
                            ->schema([
                                Forms\Components\Select::make('borrower_type')
                                    ->label('Borrower Type')
                                    ->options([
                                        'App\\Models\\User' => 'Registered User',
                                        'App\\Models\\GuestBorrower' => 'Guest',
                                    ])
                                    ->default('App\\Models\\User')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(Forms\Set $set) => $set('borrower_id', null)),

                                // Registered User Selector (visible only if borrower type is User)
                                Forms\Components\Select::make('borrower_id')
                                    ->label('Select User')
                                    ->relationship(
                                        name: 'borrower',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query->where('deleted_at', null)
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} ({$record->email})")
                                    ->searchable(['name', 'email'])
                                    ->preload()
                                    ->visible(fn(Forms\Get $get): bool => $get('borrower_type') === 'App\\Models\\User')
                                    ->required(fn(Forms\Get $get): bool => $get('borrower_type') === 'App\\Models\\User'),

                                // Guest Borrower Form (visible only if borrower type is Guest Borrower)
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('guest_name')
                                        ->label('Name')
                                        ->required(fn(Forms\Get $get): bool => $get('borrower_type') === 'App\\Models\\GuestBorrower')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guest_email')
                                        ->label('Email')
                                        ->email()
                                        ->required(fn(Forms\Get $get): bool => $get('borrower_type') === 'App\\Models\\GuestBorrower')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guest_phone')
                                        ->label('Phone')
                                        ->tel()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guest_id_number')
                                        ->label('ID Number')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guest_organization')
                                        ->label('Organization')
                                        ->maxLength(255),
                                ])
                                    ->visible(fn(Forms\Get $get): bool => $get('borrower_type') === 'App\\Models\\GuestBorrower')
                                    ->columns(2),
                            ])
                            ->columns(1),

                        Forms\Components\Section::make('Borrowed Items')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->label('Borrowed Items')
                                    ->schema([
                                        Forms\Components\Select::make('item_id')
                                            ->label('Item')
                                            ->options(function () {
                                                // Debug message to see if this callback is executed
                                                \Illuminate\Support\Facades\Log::info('Loading item options for select field');

                                                // Get all items from the database
                                                $items = \App\Models\Item::all();

                                                // Log the items count for debugging
                                                \Illuminate\Support\Facades\Log::info('Found ' . $items->count() . ' items to populate select field');

                                                // Format options differently for borrowed vs available
                                                return $items->mapWithKeys(function ($item) {
                                                    $status = $item->status;
                                                    $label = $item->name;

                                                    // Add visual indicator if borrowed
                                                    if ($status === 'borrowed') {
                                                        $label .= ' (⚠️ Already Borrowed)';
                                                    }

                                                    return [$item->id => $label];
                                                });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    $item = Item::find($state);
                                                    if ($item && $item->status === 'borrowed') {
                                                        \Filament\Notifications\Notification::make()
                                                            ->warning()
                                                            ->title('Warning')
                                                            ->body("This item is already borrowed. Adding it may cause inventory inconsistencies.")
                                                            ->persistent()
                                                            ->send();
                                                    }
                                                }
                                            }),

                                        Forms\Components\TextInput::make('deprecated_quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(function (Forms\Get $get) {
                                                $itemId = $get('item_id');
                                                if (!$itemId) return 1;

                                                $item = Item::find($itemId);
                                                return $item ? $item->isAvailable() ? 1 : 0 : 1;
                                            })
                                            ->helperText(function (Forms\Get $get) {
                                                $itemId = $get('item_id');
                                                if (!$itemId) return null;

                                                $item = Item::find($itemId);
                                                if (!$item) return null;

                                                $available = $item->isAvailable() ? 1 : 0;
                                                return "Available: {$available}";
                                            })
                                            ->required(),

                                        Forms\Components\TagsInput::make('serial_numbers')
                                            ->placeholder('Enter serial numbers')
                                            ->helperText('Add serial numbers one by one by pressing Enter after each')
                                            ->nullable(),

                                        Forms\Components\Textarea::make('condition_before')
                                            ->placeholder('Item condition before loan')
                                            ->nullable(),
                                    ])
                                    ->columns(2)
                                    ->itemLabel(
                                        fn(array $state): ?string =>
                                        Item::find($state['item_id'])?->name
                                            ? Item::find($state['item_id'])->name . ' (Qty: ' . ($state['deprecated_quantity'] ?? 1) . ')'
                                            : 'New Item'
                                    )
                                    ->addActionLabel('Add Item')
                                    ->defaultItems(1)
                                    ->reorderableWithButtons()
                                    ->collapsible(false)
                                    ->minItems(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Documentation')
                            ->schema([
                                Forms\Components\FileUpload::make('signature')
                                    ->image()
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('3:1')
                                    ->directory('signatures')
                                    ->maxSize(2048)
                                    ->helperText('Upload borrower signature (optional)'),
                                Forms\Components\TextInput::make('voucher_path')
                                    ->label('Voucher')
                                    ->readOnly()
                                    ->helperText('Voucher will be generated automatically after saving'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('borrower')
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
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where(function (Builder $query) use ($search): Builder {
                                return $query
                                    ->where('guest_name', 'like', "%{$search}%")
                                    ->orWhereHas(
                                        'user',
                                        fn(Builder $q) =>
                                        $q->where('name', 'like', "%{$search}%")
                                    )
                                    ->orWhereHasMorph(
                                        'borrower',
                                        ['App\\Models\\User', 'App\\Models\\GuestBorrower'],
                                        fn(Builder $q) =>
                                        $q->where('name', 'like', "%{$search}%")
                                    );
                            });
                    }),
                Tables\Columns\TextColumn::make('department.name')
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'active' => 'success',
                        'overdue' => 'danger',
                        'returned' => 'info',
                        'canceled' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                Tables\Columns\IconColumn::make('is_guest')
                    ->label('Guest')
                    ->boolean()
                    ->toggleable(),
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
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'overdue' => 'Overdue',
                        'returned' => 'Returned',
                        'canceled' => 'Canceled',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn(Builder $query): Builder => $query->whereNull('return_date')->whereDate('due_date', '<', now())),
                Tables\Filters\Filter::make('returned')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('return_date')),
            ])
            ->actions([
                Tables\Actions\Action::make('print_voucher')
                    ->label('Print Voucher')
                    ->url(fn(Loan $record): ?string => route('loans.voucher', $record))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-printer')
                    ->visible(fn(Loan $record): bool => $record->voucher_path !== null),
                Tables\Actions\Action::make('return')
                    ->label('Return Loan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Return Notes')
                            ->placeholder('Condition of returned items, missing parts, damage, etc.')
                    ])
                    ->action(function (Loan $record, array $data): void {
                        $record->markAsReturned($data['notes'] ?? null);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Loan Returned')
                            ->body('All items have been marked as returned.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Return Loan')
                    ->modalDescription('Mark this loan as returned? This will update the status of all borrowed items.')
                    ->visible(fn(Loan $record): bool => $record->status !== 'returned' && $record->status !== 'canceled'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }

    /**
     * Handle after create hook
     */
    public static function afterCreate(array $data): void
    {
        // Get the created loan
        $loan = static::getModel()::latest('id')->first();

        // If the loan is active or pending, update the status of attached items
        if (in_array($loan->status, ['active', 'pending', 'overdue'])) {
            foreach ($loan->items as $item) {
                // Update each individual item's status to borrowed
                $item->status = 'borrowed';
                $item->save();
            }
        }
    }

    /**
     * Handle after update hook
     */
    public static function afterUpdate($record, array $data): void
    {
        // If the loan is completed/returned
        if ($record->status === 'returned') {
            foreach ($record->items as $item) {
                // Check if the item is in any other active loans
                $stillOnLoan = $item->loans()
                    ->where('loans.id', '!=', $record->id)
                    ->whereIn('loans.status', ['active', 'pending', 'overdue'])
                    ->exists();

                // If not in any other loans, set back to available
                if (!$stillOnLoan) {
                    $item->status = 'available';
                    $item->save();
                }
            }
        } else if (in_array($record->status, ['active', 'pending', 'overdue'])) {
            // If loan is active, make sure all items are marked as borrowed
            foreach ($record->items as $item) {
                $item->status = 'borrowed';
                $item->save();
            }
        }
    }
}
