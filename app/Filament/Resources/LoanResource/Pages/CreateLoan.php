<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Services\LoanService;
use App\Exceptions\LoanCreationException;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
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

    /**
     * Override the record creation to use LoanService
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            // Use LoanService to create the loan
            $loanService = app(LoanService::class);
            $loan = $loanService->createLoan($data);

            // Show success notification
            Notification::make()
                ->success()
                ->title('Loan Created')
                ->body('The loan has been created successfully.')
                ->send();

            return $loan;
        } catch (LoanCreationException $e) {
            // Handle specific loan creation exceptions
            $errorTitle = 'Loan Creation Failed';
            $errorMessage = $e->getMessage();

            // Customize message based on error code if needed
            switch ($e->getCode()) {
                case LoanCreationException::ERROR_INVALID_BORROWER:
                    $errorTitle = 'Invalid Borrower';
                    break;
                case LoanCreationException::ERROR_INVALID_ITEMS:
                    $errorTitle = 'Invalid Items';
                    break;
                case LoanCreationException::ERROR_INSUFFICIENT_QUANTITY:
                    $errorTitle = 'Insufficient Quantity';
                    break;
                case LoanCreationException::ERROR_ITEM_ALREADY_BORROWED:
                    $errorTitle = 'Item Already Borrowed';
                    break;
            }

            // Show error notification
            Notification::make()
                ->danger()
                ->title($errorTitle)
                ->body($errorMessage)
                ->persistent()
                ->send();

            // Halt form submission - this will throw an exception and stop execution
            $this->halt();

            // This line won't be reached due to halt() but is required for type safety
            throw new \Exception('Loan creation was halted');
        }
    }
}
