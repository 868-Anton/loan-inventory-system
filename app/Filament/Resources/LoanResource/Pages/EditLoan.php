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
            // Clear existing items to avoid duplicates
            $record->items()->detach();

            // Add new items with pivot data
            foreach ($data['items'] as $item) {
                if (!empty($item['item_id'])) {
                    $record->items()->attach($item['item_id'], [
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
