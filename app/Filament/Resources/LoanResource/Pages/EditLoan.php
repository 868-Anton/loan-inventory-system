<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // First save the basic loan data
        $record->update($data);

        // Only manage items if they are in the request
        if (isset($data['items'])) {
            // Get previously attached items before detaching
            $previousItems = $record->items()->pluck('items.id')->toArray();

            // Clear existing items to avoid duplicates
            $record->items()->detach();

            // Reset status of previously attached items that are not in the new list
            $newItemIds = collect($data['items'])->pluck('item_id')->filter()->toArray();
            \App\Models\Item::whereIn('id', $previousItems)
                ->whereNotIn('id', $newItemIds)
                ->where('status', 'borrowed')
                ->update(['status' => 'available']);

            // Add new items with pivot data
            foreach ($data['items'] as $item) {
                if (!empty($item['item_id'])) {
                    // Get the item model
                    $itemModel = \App\Models\Item::find($item['item_id']);

                    if ($itemModel) {
                        // Check if item is already borrowed by another loan
                        if (
                            $itemModel->status === 'borrowed' &&
                            $itemModel->loans()->where('loans.id', '!=', $record->id)
                            ->whereIn('loans.status', ['active', 'pending', 'overdue'])
                            ->exists()
                        ) {

                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Warning')
                                ->body("Item '{$itemModel->name}' appears to be borrowed by another loan.")
                                ->send();
                        }

                        // Update the item status to borrowed
                        $itemModel->update(['status' => 'borrowed']);

                        // Attach to the loan with pivot data
                        $record->items()->attach($itemModel->id, [
                            'quantity' => $item['quantity'] ?? 1,
                            'serial_numbers' => !empty($item['serial_numbers']) ? json_encode($item['serial_numbers']) : null,
                            'condition_before' => $item['condition_before'] ?? null,
                            'status' => 'loaned',
                        ]);
                    }
                }
            }
        }

        return $record;
    }
}
