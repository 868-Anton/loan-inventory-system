<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Services\LoanService;
use App\Exceptions\LoanCreationException;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
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

    protected function getFormModel(): Model
    {
        $model = parent::getFormModel();

        // If we have a guest borrower, pre-fill the guest borrower fields
        if ($model->borrower_type === 'App\\Models\\GuestBorrower' && $model->borrower) {
            $guestBorrower = $model->borrower;
            $this->form->fill([
                'guest_name' => $guestBorrower->name,
                'guest_email' => $guestBorrower->email,
                'guest_phone' => $guestBorrower->phone,
                'guest_id_number' => $guestBorrower->id_number,
                'guest_organization' => $guestBorrower->organization,
            ]);
        }

        return $model;
    }

    /**
     * Override the record update method to use LoanService
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            // Use LoanService to update the loan
            $loanService = app(LoanService::class);
            $loan = $loanService->updateLoan($record, $data);

            // Show success notification
            Notification::make()
                ->success()
                ->title('Loan Updated')
                ->body('The loan has been updated successfully.')
                ->send();

            return $loan;
        } catch (LoanCreationException $e) {
            // Handle specific loan exceptions
            $errorTitle = 'Loan Update Failed';
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
            throw new \Exception('Loan update was halted');
        }
    }
}
