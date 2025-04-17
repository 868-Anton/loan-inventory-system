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
        // If loan is active, update all attached items to borrowed status
        if ($loan->status === 'active') {
            $this->updateItemsStatus($loan);
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
        elseif ($loan->status === 'active' && $loan->getOriginal('status') !== 'active') {
            $this->updateItemsStatus($loan);
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
     * Handle the Loan "deleted" event.
     */
    public function deleted(Loan $loan): void
    {
        //
    }

    /**
     * Handle the Loan "restored" event.
     */
    public function restored(Loan $loan): void
    {
        //
    }

    /**
     * Handle the Loan "force deleted" event.
     */
    public function forceDeleted(Loan $loan): void
    {
        //
    }
}
