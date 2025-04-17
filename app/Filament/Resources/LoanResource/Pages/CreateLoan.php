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
                $record->items()->attach($item['item_id'], [
                    'quantity' => $item['quantity'] ?? 1,
                    'serial_numbers' => !empty($item['serial_numbers']) ? json_encode($item['serial_numbers']) : null,
                    'condition_before' => $item['condition_before'] ?? null,
                    'status' => 'loaned',
                ]);
            }
        }

        return $record;
    }
}
