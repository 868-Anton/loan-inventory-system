<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    /**
     * Support for pre-filling the form with item data passed via URL parameters
     */
    protected function getFormData(): array
    {
        $data = parent::getFormData();

        // Get items from request params if available
        $requestItems = request()->query('items');
        Log::info('URL params detected', ['items' => $requestItems]);

        if (!empty($requestItems) && is_array($requestItems)) {
            $data['items'] = [];

            // Process each item in the request
            foreach ($requestItems as $requestItem) {
                if (isset($requestItem['item_id'])) {
                    $item = \App\Models\Item::find($requestItem['item_id']);

                    if ($item) {
                        Log::info('Pre-filling item', [
                            'id' => $item->id,
                            'name' => $item->name,
                            'status' => $item->status
                        ]);

                        // Add the item to the form data
                        $data['items'][] = [
                            'item_id' => $item->id,
                            'quantity' => $requestItem['quantity'] ?? 1,
                        ];
                    } else {
                        Log::warning('Item not found for prefill', ['item_id' => $requestItem['item_id']]);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * This method is called when the form is first mounted
     * Use it to ensure the repeater has at least one row when we have URL params
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Fill the form with initial data if items are passed via URL
        $requestItems = request()->query('items');

        if (!empty($requestItems) && is_array($requestItems) && empty($data['items'])) {
            Log::info('Creating default item slot in repeater');
            // Ensure we create at least one row in the repeater
            $data['items'] = [[
                'item_id' => null,
                'quantity' => 1,
            ]];
        }

        return $data;
    }

    // This tells Filament that we're using parameters from the 'prefill' query parameter
    protected function fillForm(): void
    {
        if ($this->getResource()::canCreate()) {
            $prefillData = request()->query('prefill', []);
            $this->form->fill($prefillData);
        }
    }

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
                    // Check if item is already borrowed by another loan
                    $borrowedByOtherLoan = $itemModel->status === 'borrowed' &&
                        $itemModel->loans()
                        ->whereIn('loans.status', ['active', 'pending', 'overdue'])
                        ->exists();

                    if ($borrowedByOtherLoan) {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Warning')
                            ->body("Item '{$itemModel->name}' appears to be borrowed by another loan.")
                            ->persistent()
                            ->send();
                    }

                    // Always update the item status to borrowed when it's part of an active loan
                    if (in_array($record->status, ['active', 'pending', 'overdue'])) {
                        $itemModel->update(['status' => 'borrowed']);
                    }

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
