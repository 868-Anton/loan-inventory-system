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
                                    ->options(function () {
                                        return \App\Models\User::where('deleted_at', null)
                                            ->get()
                                            ->pluck('nameWithEmail', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
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

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
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
                                            ? Item::find($state['item_id'])->name . ' (Qty: ' . ($state['quantity'] ?? 1) . ')'
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
                    ->searchable()
                    ->url(fn(Loan $record): string => route('filament.admin.resources.loans.view', $record))
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\ViewAction::make(),
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
                        Forms\Components\Section::make('Select Items to Return')
                            ->schema([
                                Forms\Components\CheckboxList::make('items_to_return')
                                    ->label('Items to Return')
                                    ->options(function ($record) {
                                        return $record->items->mapWithKeys(function ($item) {
                                            $label = $item->name;
                                            if ($item->pivot->serial_numbers && !empty($item->pivot->serial_numbers)) {
                                                $serialNumbers = is_array($item->pivot->serial_numbers)
                                                    ? $item->pivot->serial_numbers
                                                    : json_decode($item->pivot->serial_numbers, true);

                                                if (is_array($serialNumbers) && !empty($serialNumbers)) {
                                                    $label .= ' (SN: ' . implode(', ', $serialNumbers) . ')';
                                                }
                                            }
                                            return [$item->id => $label];
                                        })->toArray();
                                    })
                                    ->default(function ($record) {
                                        return $record->items->pluck('id')->toArray();
                                    })
                                    ->columns(1)
                                    ->required()
                                    ->helperText('Select which items you want to return. All items are selected by default.'),
                            ]),

                        Forms\Components\Section::make('Return Conditions')
                            ->schema([
                                Forms\Components\Radio::make('condition_mode')
                                    ->label('Condition Setting Mode')
                                    ->options([
                                        'bulk' => 'Set same condition for all selected items',
                                        'individual' => 'Set condition for each item individually',
                                    ])
                                    ->default('bulk')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $record) {
                                        if ($state === 'individual') {
                                            // Pre-populate individual conditions when individual mode is selected
                                            $selectedItems = $record->items;
                                            $defaultItems = $selectedItems->map(function ($item) {
                                                $serialNumbersText = '';
                                                if ($item->pivot->serial_numbers && !empty($item->pivot->serial_numbers)) {
                                                    $serialNumbers = is_array($item->pivot->serial_numbers)
                                                        ? $item->pivot->serial_numbers
                                                        : json_decode($item->pivot->serial_numbers, true);

                                                    if (is_array($serialNumbers) && !empty($serialNumbers)) {
                                                        $serialNumbersText = ' (SN: ' . implode(', ', $serialNumbers) . ')';
                                                    }
                                                }

                                                return [
                                                    'item_id' => $item->id,
                                                    'item_name' => $item->name . $serialNumbersText,
                                                    'condition_tags' => [],
                                                    'return_notes' => '',
                                                ];
                                            })->toArray();

                                            $set('individual_conditions', $defaultItems);
                                        }
                                    }),

                                // Bulk condition settings (shown when bulk mode is selected)
                                Forms\Components\Group::make([
                                    Forms\Components\Select::make('bulk_condition_tags')
                                        ->label('Condition Tags (All Items)')
                                        ->multiple()
                                        ->searchable()
                                        ->options(\App\Helpers\ConditionTags::grouped())
                                        ->placeholder('Select condition tags for all items')
                                        ->required(false)
                                        ->afterStateUpdated(function ($state, $set) {
                                            if (!$state || empty($state)) {
                                                return;
                                            }

                                            if (\App\Helpers\ConditionTags::hasGoodTags($state) && \App\Helpers\ConditionTags::hasIssueTags($state)) {
                                                $filteredTags = \App\Helpers\ConditionTags::filterToGoodTags($state);
                                                $set('bulk_condition_tags', $filteredTags);

                                                \Filament\Notifications\Notification::make()
                                                    ->warning()
                                                    ->title('Tag Conflict Resolved')
                                                    ->body('Good condition tags cannot be selected with issue tags. Only good condition tags have been kept.')
                                                    ->send();
                                            }
                                        })
                                        ->helperText('Note: Good condition tags cannot be selected with issue tags.')
                                        ->visible(fn(Forms\Get $get) => $get('condition_mode') === 'bulk'),

                                    Forms\Components\Textarea::make('bulk_return_notes')
                                        ->label('Return Notes (All Items)')
                                        ->placeholder('Add notes that apply to all selected items')
                                        ->rows(3)
                                        ->visible(fn(Forms\Get $get) => $get('condition_mode') === 'bulk'),
                                ]),

                                // Individual condition settings (shown when individual mode is selected)
                                Forms\Components\Repeater::make('individual_conditions')
                                    ->label('Individual Item Conditions')
                                    ->schema([
                                        Forms\Components\Hidden::make('item_id'),
                                        Forms\Components\TextInput::make('item_name')
                                            ->label('Item')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\Select::make('condition_tags')
                                            ->label('Condition Tags')
                                            ->multiple()
                                            ->searchable()
                                            ->options(\App\Helpers\ConditionTags::grouped())
                                            ->placeholder('Select condition tags')
                                            ->required(false)
                                            ->afterStateUpdated(function ($state, $set) {
                                                if (!$state || empty($state)) {
                                                    return;
                                                }

                                                if (\App\Helpers\ConditionTags::hasGoodTags($state) && \App\Helpers\ConditionTags::hasIssueTags($state)) {
                                                    $filteredTags = \App\Helpers\ConditionTags::filterToGoodTags($state);
                                                    $set('condition_tags', $filteredTags);

                                                    \Filament\Notifications\Notification::make()
                                                        ->warning()
                                                        ->title('Tag Conflict Resolved')
                                                        ->body('Good condition tags cannot be selected with issue tags. Only good condition tags have been kept.')
                                                        ->send();
                                                }
                                            }),
                                        Forms\Components\Textarea::make('return_notes')
                                            ->label('Return Notes')
                                            ->placeholder('Add notes for this specific item')
                                            ->rows(2),
                                    ])
                                    ->columns(1)
                                    ->itemLabel(fn(array $state): ?string => $state['item_name'] ?? null)
                                    ->visible(fn(Forms\Get $get) => $get('condition_mode') === 'individual')
                                    ->defaultItems(0),
                            ]),
                    ])
                    ->action(function (Loan $record, array $data): void {
                        try {
                            $returnedBy = \Filament\Facades\Filament::auth()->user()?->name ?? 'System';
                            $selectedItemIds = $data['items_to_return'] ?? [];
                            $conditionMode = $data['condition_mode'] ?? 'bulk';

                            // Update loan status if all items are being returned
                            $allItemsSelected = count($selectedItemIds) === $record->items->count();

                            if ($allItemsSelected) {
                                $record->update([
                                    'status' => 'returned',
                                    'return_date' => now(),
                                ]);
                            }

                            // Process each selected item
                            foreach ($selectedItemIds as $itemId) {
                                $item = $record->items->firstWhere('id', $itemId);
                                if (!$item) continue;

                                if ($conditionMode === 'bulk') {
                                    // Use bulk conditions for all items
                                    $record->returnItem(
                                        $itemId,
                                        $data['bulk_condition_tags'] ?? null,
                                        $data['bulk_return_notes'] ?? null,
                                        $returnedBy
                                    );
                                } else {
                                    // Use individual conditions
                                    $individualCondition = collect($data['individual_conditions'] ?? [])
                                        ->firstWhere('item_id', $itemId);

                                    if ($individualCondition) {
                                        $record->returnItem(
                                            $itemId,
                                            $individualCondition['condition_tags'] ?? null,
                                            $individualCondition['return_notes'] ?? null,
                                            $returnedBy
                                        );
                                    }
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Items Returned')
                                ->body(count($selectedItemIds) . ' item(s) have been marked as returned.')
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Return Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Return Items')
                    ->modalDescription('Select items to return and set their conditions.')
                    ->visible(fn(Loan $record): bool => $record->status !== 'returned' && $record->status !== 'canceled'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'view' => Pages\ViewLoan::route('/{record}'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
            'return' => Pages\ReturnLoan::route('/return'),
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
