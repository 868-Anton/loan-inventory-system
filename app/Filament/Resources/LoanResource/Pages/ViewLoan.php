<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Helpers\ConditionTags;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

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
          Forms\Components\Section::make('Select Items to Return')
            ->schema([
              Forms\Components\CheckboxList::make('items_to_return')
                ->label('Items to Return')
                ->options(function () {
                  return $this->record->items->mapWithKeys(function ($item) {
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
                ->default(function () {
                  return $this->record->items->pluck('id')->toArray();
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
                ->reactive(),

              // Bulk condition settings (shown when bulk mode is selected)
              Forms\Components\Group::make([
                Forms\Components\Select::make('bulk_condition_tags')
                  ->label('Condition Tags (All Items)')
                  ->multiple()
                  ->searchable()
                  ->options(ConditionTags::grouped())
                  ->placeholder('Select condition tags for all items')
                  ->required(false)
                  ->afterStateUpdated(function ($state, $set) {
                    if (!$state || empty($state)) {
                      return;
                    }

                    if (ConditionTags::hasGoodTags($state) && ConditionTags::hasIssueTags($state)) {
                      $filteredTags = ConditionTags::filterToGoodTags($state);
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
                    ->options(ConditionTags::grouped())
                    ->placeholder('Select condition tags')
                    ->required(false)
                    ->afterStateUpdated(function ($state, $set) {
                      if (!$state || empty($state)) {
                        return;
                      }

                      if (ConditionTags::hasGoodTags($state) && ConditionTags::hasIssueTags($state)) {
                        $filteredTags = ConditionTags::filterToGoodTags($state);
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
                ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                  // Pre-populate with selected items
                  $selectedItems = $this->record->items;
                  return $selectedItems->map(function ($item) {
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
                }),
            ]),
        ])
        ->action(function (array $data): void {
          try {
            $returnedBy = \Filament\Facades\Filament::auth()->user()?->name ?? 'System';
            $selectedItemIds = $data['items_to_return'] ?? [];
            $conditionMode = $data['condition_mode'] ?? 'bulk';

            // Update loan status if all items are being returned
            $allItemsSelected = count($selectedItemIds) === $this->record->items->count();

            if ($allItemsSelected) {
              $this->record->update([
                'status' => 'returned',
                'return_date' => now(),
              ]);
            }

            // Process each selected item
            foreach ($selectedItemIds as $itemId) {
              $item = $this->record->items->firstWhere('id', $itemId);
              if (!$item) continue;

              if ($conditionMode === 'bulk') {
                // Use bulk conditions for all items
                $this->record->returnItem(
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
                  $this->record->returnItem(
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

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
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

        Infolists\Components\Section::make('Return Information')
          ->schema([
            Infolists\Components\TextEntry::make('condition_tags')
              ->label('Condition Tags')
              ->formatStateUsing(fn($state) => ConditionTags::formatForDisplay($state))
              ->visible(fn($record) => $record->status === 'returned'),
            Infolists\Components\TextEntry::make('return_notes')
              ->label('Return Notes')
              ->visible(fn($record) => filled($record->return_notes)),
          ])
          ->columns(2)
          ->visible(fn($record) => $record->status === 'returned'),
      ]);
  }

  public function table(Tables\Table $table): Tables\Table
  {
    return $table
      ->relationship(fn() => $this->record->items())
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->label('Item Name')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('category.name')
          ->label('Category')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('description')
          ->label('Description')
          ->limit(50)
          ->tooltip(fn(string $state): string => $state),
        Tables\Columns\TextColumn::make('pivot.serial_numbers')
          ->label('Serial Numbers')
          ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
        Tables\Columns\TextColumn::make('pivot.condition_before')
          ->label('Condition Before')
          ->limit(50)
          ->tooltip(fn(string $state): string => $state),
        Tables\Columns\TextColumn::make('pivot.condition_tags')
          ->label('Condition Tags')
          ->formatStateUsing(fn($state) => ConditionTags::formatForDisplay($state))
          ->visible(fn($record) => $record->pivot->condition_tags && !empty($record->pivot->condition_tags)),
        Tables\Columns\TextColumn::make('pivot.return_notes')
          ->label('Return Notes')
          ->limit(50)
          ->tooltip(fn(string $state): string => $state)
          ->visible(fn($record) => filled($record->pivot->return_notes)),
        Tables\Columns\TextColumn::make('pivot.returned_by')
          ->label('Returned By')
          ->visible(fn($record) => filled($record->pivot->returned_by)),
        Tables\Columns\TextColumn::make('pivot.status')
          ->label('Status')
          ->badge()
          ->color(fn(string $state): string => match (strtolower($state)) {
            'loaned' => 'warning',
            'returned' => 'success',
            'damaged' => 'danger',
            'lost' => 'danger',
            default => 'gray',
          }),
        Tables\Columns\TextColumn::make('pivot.returned_at')
          ->label('Returned At')
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('pivot.condition_assessed_at')
          ->label('Assessed At')
          ->dateTime()
          ->sortable()
          ->visible(fn($record) => $record->pivot->condition_assessed_at),
      ])
      ->actions([
        Tables\Actions\Action::make('returnItem')
          ->label('Return Item')
          ->icon('heroicon-o-arrow-uturn-left')
          ->color('success')
          ->form([
            Forms\Components\Select::make('condition_tags')
              ->label('Condition Tags')
              ->multiple()
              ->searchable()
              ->options(ConditionTags::grouped())
              ->placeholder('Select item condition tags')
              ->required(false)
              ->afterStateUpdated(function ($state, $set) {
                if (!$state || empty($state)) {
                  return;
                }

                // Check if both good condition and issue tags are selected
                if (ConditionTags::hasGoodTags($state) && ConditionTags::hasIssueTags($state)) {
                  // Keep only good condition tags
                  $filteredTags = ConditionTags::filterToGoodTags($state);
                  $set('condition_tags', $filteredTags);

                  // Show notification about the conflict
                  \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Tag Conflict Resolved')
                    ->body('Good condition tags cannot be selected with issue tags. Only good condition tags have been kept.')
                    ->send();
                }
              })
              ->helperText('Note: Good condition tags cannot be selected with issue tags. Selecting a good condition tag will automatically clear any issue tags.'),
            Forms\Components\Textarea::make('condition_after')
              ->label('Condition Notes')
              ->placeholder('Enter any additional notes about the condition of the item after return')
              ->rows(3),
          ])
          ->requiresConfirmation()
          ->modalHeading('Return Item')
          ->modalDescription('Are you sure you want to mark this item as returned?')
          ->action(function (array $data, $record): void {
            try {
              // Get current user name for returned_by field
              $returnedBy = \Filament\Facades\Filament::auth()->user()?->name ?? 'System';

              $this->record->returnItem(
                $record->id,
                $data['condition_tags'] ?? null,
                $data['condition_after'] ?? null,
                $returnedBy
              );

              \Filament\Notifications\Notification::make()
                ->success()
                ->title('Item Returned')
                ->body('The item has been marked as returned with condition assessment.')
                ->send();

              $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
            } catch (\Exception $e) {
              \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Item Return Failed')
                ->body('Error: ' . $e->getMessage())
                ->send();
            }
          })
          ->visible(fn($record) => !$record->pivot->returned_at && $record->pivot->status !== 'returned'),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('status')
          ->options([
            'loaned' => 'Loaned',
            'returned' => 'Returned',
            'damaged' => 'Damaged',
            'lost' => 'Lost',
          ])
          ->attribute('pivot.status'),
      ])
      ->heading('Borrowed Items');
  }
}
