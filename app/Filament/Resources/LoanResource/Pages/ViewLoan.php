<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
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
          Forms\Components\Select::make('condition_tags')
            ->label('Condition Tags')
            ->multiple()
            ->searchable()
            ->options([
              '✅ Good Condition' => [
                'returned-no-issues' => 'Returned with no issues',
                'fully-functional' => 'Fully functional',
                'clean-and-intact' => 'Clean and intact',
              ],
              '🧩 Missing Parts' => [
                'missing-accessories' => 'Missing accessories',
                'missing-components' => 'Missing components',
                'incomplete-set' => 'Incomplete set',
                'missing-manual-or-packaging' => 'Missing manual or packaging',
              ],
              '🔨 Physical Damage' => [
                'damaged-cracked' => 'Cracked',
                'damaged-dented' => 'Dented',
                'broken-screen' => 'Broken screen',
                'structural-damage' => 'Structural damage',
              ],
              '🛠 Needs Repair' => [
                'non-functional' => 'Non-functional',
                'requires-maintenance' => 'Requires maintenance',
                'battery-issues' => 'Battery issues',
              ],
              '🧼 Sanitation Issues' => [
                'dirty-needs-cleaning' => 'Dirty, needs cleaning',
                'contaminated' => 'Contaminated',
                'odor-present' => 'Odor present',
              ],
              '⚠️ Other Conditions' => [
                'label-or-seal-removed' => 'Label/seal removed',
                'unauthorized-modification' => 'Unauthorized modification',
                'returned-late' => 'Returned late',
              ],
            ])
            ->placeholder('Select item condition tags')
            ->required(false)
            ->afterStateUpdated(function ($state, $set) {
              if (!$state || empty($state)) {
                return;
              }

              // Define good condition tags
              $goodConditionTags = ['returned-no-issues', 'fully-functional', 'clean-and-intact'];

              // Check if any good condition tags are selected
              $hasGoodCondition = collect($state)->intersect($goodConditionTags)->isNotEmpty();

              // Check if any issue tags are selected
              $hasIssueTags = collect($state)->diff($goodConditionTags)->isNotEmpty();

              if ($hasGoodCondition && $hasIssueTags) {
                // If both good condition and issue tags are selected, keep only good condition tags
                $filteredTags = collect($state)->intersect($goodConditionTags)->values()->toArray();
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
          Forms\Components\Textarea::make('return_notes')
            ->label('Additional Notes')
            ->placeholder('Add extra notes if necessary')
            ->rows(3),
        ])
        ->action(function (array $data): void {
          $this->record->update([
            'condition_tags' => $data['condition_tags'] ?? [],
            'return_notes' => $data['return_notes'] ?? '',
            'status' => 'returned',
            'return_date' => now(),
          ]);

          \Filament\Notifications\Notification::make()
            ->success()
            ->title('Loan Returned')
            ->body('All items have been marked as returned with condition details.')
            ->send();
        })
        ->requiresConfirmation()
        ->modalHeading('Return Loan')
        ->modalDescription('Mark this loan as returned? This will update the status of all borrowed items.')
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
              ->formatStateUsing(function ($state) {
                if (!$state || empty($state)) {
                  return 'No tags';
                }

                return collect($state)->map(function ($tag) {
                  // Convert tag format to readable text
                  $parts = explode('.', $tag);
                  if (count($parts) === 2) {
                    $category = ucwords(str_replace('_', ' ', $parts[0]));
                    $condition = ucwords(str_replace('-', ' ', $parts[1]));
                    return "{$category}: {$condition}";
                  }
                  return ucwords(str_replace(['-', '_'], ' ', $tag));
                })->join(', ');
              })
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
      ->relationship('items')
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
              ->options([
                '✅ Good Condition' => [
                  'returned-no-issues' => 'Returned with no issues',
                  'fully-functional' => 'Fully functional',
                  'clean-and-intact' => 'Clean and intact',
                ],
                '🧩 Missing Parts' => [
                  'missing-accessories' => 'Missing accessories',
                  'missing-components' => 'Missing components',
                  'incomplete-set' => 'Incomplete set',
                  'missing-manual-or-packaging' => 'Missing manual or packaging',
                ],
                '🔨 Physical Damage' => [
                  'damaged-cracked' => 'Cracked',
                  'damaged-dented' => 'Dented',
                  'broken-screen' => 'Broken screen',
                  'structural-damage' => 'Structural damage',
                ],
                '🛠 Needs Repair' => [
                  'non-functional' => 'Non-functional',
                  'requires-maintenance' => 'Requires maintenance',
                  'battery-issues' => 'Battery issues',
                ],
                '🧼 Sanitation Issues' => [
                  'dirty-needs-cleaning' => 'Dirty, needs cleaning',
                  'contaminated' => 'Contaminated',
                  'odor-present' => 'Odor present',
                ],
                '⚠️ Other Conditions' => [
                  'label-or-seal-removed' => 'Label/seal removed',
                  'unauthorized-modification' => 'Unauthorized modification',
                  'returned-late' => 'Returned late',
                ],
              ])
              ->placeholder('Select item condition tags')
              ->required(false)
              ->afterStateUpdated(function ($state, $set) {
                if (!$state || empty($state)) {
                  return;
                }

                // Define good condition tags
                $goodConditionTags = ['returned-no-issues', 'fully-functional', 'clean-and-intact'];

                // Check if any good condition tags are selected
                $hasGoodCondition = collect($state)->intersect($goodConditionTags)->isNotEmpty();

                // Check if any issue tags are selected
                $hasIssueTags = collect($state)->diff($goodConditionTags)->isNotEmpty();

                if ($hasGoodCondition && $hasIssueTags) {
                  // If both good condition and issue tags are selected, keep only good condition tags
                  $filteredTags = collect($state)->intersect($goodConditionTags)->values()->toArray();
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
            $this->record->returnItem($record->id, $data['condition_after'] ?? null);

            \Filament\Notifications\Notification::make()
              ->success()
              ->title('Item Returned')
              ->body('The item has been marked as returned.')
              ->send();

            $this->refresh();
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
