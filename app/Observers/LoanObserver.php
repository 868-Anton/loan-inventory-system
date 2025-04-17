<?php

namespace App\Observers;

use App\Models\Loan;

class LoanObserver
{
    /**
     * Handle the Loan "created" event.
     */
    public function created(Loan $loan): void
    {
        // Update all attached items to borrowed status
        // regardless of loan status to ensure consistency
        $this->updateItemsStatus($loan);

        // If status is not pending or active, set it back to appropriate status
        if (!in_array($loan->status, ['pending', 'active', 'overdue'])) {
            $this->handleInactiveStatus($loan);
        }
    }

    /**
     * Handle the Loan "updated" event.
     */
    public function updated(Loan $loan): void
    {
        // If the loan status changed to returned, update the item statuses
        if ($loan->status === 'returned' && $loan->getOriginal('status') !== 'returned') {
            // Update all associated items status in the pivot table
            $loan->items()->updateExistingPivot(
                $loan->items->pluck('id')->toArray(),
                ['status' => 'returned']
            );

            // For each item, check if it's in any other active loans
            // If not, set it back to 'available'
            foreach ($loan->items as $item) {
                $stillOnLoan = $item->loans()
                    ->where('loans.id', '!=', $loan->id)
                    ->whereIn('loans.status', ['active', 'pending', 'overdue'])
                    ->exists();

                if (!$stillOnLoan) {
                    $item->update(['status' => 'available']);
                }
            }
        }
        // If the loan status changed to active, update all items to borrowed
        elseif (
            in_array($loan->status, ['active', 'pending', 'overdue']) &&
            !in_array($loan->getOriginal('status'), ['active', 'pending', 'overdue'])
        ) {
            $this->updateItemsStatus($loan);
        }
        // If the loan status changed to canceled, update items as needed
        elseif ($loan->status === 'canceled' && $loan->getOriginal('status') !== 'canceled') {
            $this->handleInactiveStatus($loan);
        }
    }

    /**
     * Update all items in a loan to borrowed status
     */
    private function updateItemsStatus(Loan $loan): void
    {
        foreach ($loan->items as $item) {
            $item->update(['status' => 'borrowed']);
        }
    }

    /**
     * Handle when a loan is set to an inactive status like canceled
     */
    private function handleInactiveStatus(Loan $loan): void
    {
        // For each item, check if it's in any other active loans
        // If not, set it back to 'available'
        foreach ($loan->items as $item) {
            $stillOnLoan = $item->loans()
                ->where('loans.id', '!=', $loan->id)
                ->whereIn('loans.status', ['active', 'pending', 'overdue'])
                ->exists();

            if (!$stillOnLoan) {
                $item->update(['status' => 'available']);
            }
        }
    }

    /**
     * Handle the Loan "deleted" event.
     */
    public function deleted(Loan $loan): void
    {
        // When a loan is deleted, check if items should be set back to available
        $this->handleInactiveStatus($loan);
    }

    /**
     * Handle the Loan "restored" event.
     */
    public function restored(Loan $loan): void
    {
        // If loan is active, update all attached items to borrowed status
        if (in_array($loan->status, ['active', 'pending', 'overdue'])) {
            $this->updateItemsStatus($loan);
        }
    }

    /**
     * Handle the Loan "force deleted" event.
     */
    public function forceDeleted(Loan $loan): void
    {
        // Same as soft delete
        $this->handleInactiveStatus($loan);
    }
}
