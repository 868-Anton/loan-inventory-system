<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Extract items data
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Create the loan record
        $record = static::getModel()::create($data);

        // Add items with pivot data
        foreach ($items as $item) {
            if (!empty($item['item_id'])) {
                // Get the item model
                $itemModel = \App\Models\Item::find($item['item_id']);

                if ($itemModel) {
                    // Check if item is already borrowed
                    if ($itemModel->status === 'borrowed') {
                        // You might want to handle this case differently
                        // For now, we'll proceed but you could add a notification or validation
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Warning')
                            ->body("Item '{$itemModel->name}' is already marked as borrowed.")
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

        return $record;
    }
}
