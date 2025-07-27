<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LoanCreationException;
use App\Models\GuestBorrower;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Service class for managing loans
 */
final class LoanService
{
  /**
   * @var Loan The loan model
   */
  private Loan $loanModel;

  /**
   * @var Item The item model
   */
  private Item $itemModel;

  /**
   * @var Dispatcher The event dispatcher
   */
  private Dispatcher $eventDispatcher;

  /**
   * Create a new LoanService instance
   */
  public function __construct(Loan $loanModel, Item $itemModel, Dispatcher $eventDispatcher)
  {
    $this->loanModel = $loanModel;
    $this->itemModel = $itemModel;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Create a new loan with the given data
   *
   * @param array $data The loan data
   * @return Loan The created loan
   * @throws LoanCreationException If loan creation fails
   */
  public function createLoan(array $data): Loan
  {
    // Validate that we have borrower information
    $this->validateBorrowerData($data);

    // Extract items data before creating the loan
    $items = $data['items'] ?? [];
    unset($data['items']);

    // Handle guest borrower creation if needed
    if ($this->isGuestBorrower($data)) {
      $data = $this->processGuestBorrower($data);
    }

    // Begin a database transaction
    DB::beginTransaction();

    try {
      // Create the loan record
      $loan = $this->loanModel->create($data);

      // If we have items, validate them and assign to the loan
      if (!empty($items)) {
        $this->validateAndAssignItems($loan, $items);
      }

      // Commit the transaction
      DB::commit();

      // Generate the loan voucher after successful creation
      $this->generateVoucher($loan);

      return $loan;
    } catch (Throwable $e) {
      // Rollback the transaction on failure
      DB::rollBack();

      // Log the error
      Log::error('Loan creation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $data
      ]);

      // Re-throw as a LoanCreationException
      if ($e instanceof LoanCreationException) {
        throw $e;
      }

      throw new LoanCreationException(
        'Failed to create loan: ' . $e->getMessage(),
        is_numeric($e->getCode()) ? (int) $e->getCode() : 0,
        $e
      );
    }
  }

  /**
   * Validate the borrower data
   *
   * @param array $data The loan data
   * @throws LoanCreationException If the borrower data is invalid
   */
  private function validateBorrowerData(array $data): void
  {
    // Check if borrower_type is specified
    if (empty($data['borrower_type'])) {
      throw LoanCreationException::invalidBorrower('Borrower type is required');
    }

    // Check if it's a valid borrower type
    if (!in_array($data['borrower_type'], ['App\\Models\\User', 'App\\Models\\GuestBorrower'])) {
      throw LoanCreationException::invalidBorrower('Invalid borrower type');
    }

    // For User borrowers, ensure we have a borrower_id
    if ($data['borrower_type'] === 'App\\Models\\User' && empty($data['borrower_id'])) {
      throw LoanCreationException::invalidBorrower('User ID is required for registered borrowers');
    }

    // For GuestBorrower, ensure we have name and email
    if ($data['borrower_type'] === 'App\\Models\\GuestBorrower') {
      if (empty($data['guest_name'])) {
        throw LoanCreationException::missingRequiredData('guest_name');
      }
      if (empty($data['guest_email'])) {
        throw LoanCreationException::missingRequiredData('guest_email');
      }
    }
  }

  /**
   * Check if the borrower is a guest
   *
   * @param array $data The loan data
   * @return bool True if the borrower is a guest
   */
  private function isGuestBorrower(array $data): bool
  {
    return isset($data['borrower_type']) && $data['borrower_type'] === 'App\\Models\\GuestBorrower';
  }

  /**
   * Process guest borrower data
   *
   * @param array $data The loan data
   * @return array The processed loan data
   */
  private function processGuestBorrower(array $data): array
  {
    // Extract guest data
    $guestData = [
      'name' => $data['guest_name'] ?? null,
      'email' => $data['guest_email'] ?? null,
      'phone' => $data['guest_phone'] ?? null,
      'id_number' => $data['guest_id_number'] ?? null,
      'organization' => $data['guest_organization'] ?? null,
    ];

    // Remove guest data from loan record
    unset(
      $data['guest_name'],
      $data['guest_email'],
      $data['guest_phone'],
      $data['guest_id_number'],
      $data['guest_organization']
    );

    // Create guest borrower record
    $guestBorrower = GuestBorrower::create($guestData);

    // Set the borrower_id to the new guest borrower
    $data['borrower_id'] = $guestBorrower->id;

    return $data;
  }

  /**
   * Validate and assign items to the loan
   *
   * @param Loan $loan The loan
   * @param array $items The items to assign
   * @throws LoanCreationException If item validation fails
   */
  private function validateAndAssignItems(Loan $loan, array $items): void
  {
    foreach ($items as $itemData) {
      if (empty($itemData['item_id'])) {
        throw LoanCreationException::invalidItems('Item ID is required');
      }

      $item = $this->itemModel->find($itemData['item_id']);
      if (!$item) {
        throw LoanCreationException::invalidItems("Item with ID {$itemData['item_id']} not found");
      }

      // Check if item is already borrowed
      $borrowedByOtherLoan = $item->status === 'borrowed' &&
        $item->loans()
        ->whereIn('loans.status', ['active', 'pending', 'overdue'])
        ->exists();

      if ($borrowedByOtherLoan) {
        throw LoanCreationException::itemAlreadyBorrowed($item->name);
      }

      // Get the requested quantity
      $quantity = $itemData['deprecated_quantity'] ?? $itemData['quantity'] ?? 1;

      // Check if we have enough quantity
      $availableQuantity = $item->isAvailable() ? 1 : 0;
      if ($quantity > $availableQuantity) {
        throw LoanCreationException::insufficientQuantity($item->name, $quantity, $availableQuantity);
      }

      // Validate serial numbers if provided
      if (!empty($itemData['serial_numbers'])) {
        $this->validateSerialNumbers($item, $itemData['serial_numbers']);
      }

      // Update item status if loan is active/pending/overdue
      if (in_array($loan->status, ['active', 'pending', 'overdue'])) {
        $item->update(['status' => 'borrowed']);
      }

      // Attach to the loan with pivot data
      $loan->items()->attach($item->id, [
        'deprecated_quantity' => $quantity,
        'serial_numbers' => !empty($itemData['serial_numbers']) ? json_encode($itemData['serial_numbers']) : null,
        'condition_before' => $itemData['condition_before'] ?? null,
        'status' => 'loaned',
      ]);
    }
  }

  /**
   * Validate serial numbers for an item
   *
   * @param Item $item The item
   * @param array $serialNumbers The serial numbers to validate
   * @throws LoanCreationException If serial validation fails
   */
  private function validateSerialNumbers(Item $item, array $serialNumbers): void
  {
    // Basic validation: ensure we have serial numbers
    if (empty($serialNumbers)) {
      return;
    }

    // Implementation will depend on specific business rules
    // For example, checking if serials match the item's known serials
    // or if they are already used in another active loan
  }

  /**
   * Generate a voucher for the loan
   *
   * @param Loan $loan The loan
   * @return string|null The path to the generated voucher
   * @throws LoanCreationException If voucher generation fails
   */
  private function generateVoucher(Loan $loan): ?string
  {
    try {
      // Use the existing voucher generation method
      return $loan->generateVoucher();
    } catch (Throwable $e) {
      Log::error('Voucher generation failed', [
        'loan_id' => $loan->id,
        'error' => $e->getMessage()
      ]);

      throw LoanCreationException::voucherGenerationFailed($e->getMessage());
    }
  }

  /**
   * Return a loan with the given data
   *
   * @param Loan $loan The loan to return
   * @param array $returnData Data related to the return (conditions, notes, etc.)
   * @return Loan The updated loan
   * @throws InvalidArgumentException If the loan is invalid
   */
  public function returnLoan(Loan $loan, array $returnData = []): Loan
  {
    // Validate the loan can be returned
    if ($loan->status === 'returned') {
      throw new InvalidArgumentException("Loan #{$loan->loan_number} is already returned");
    }

    if ($loan->status === 'canceled') {
      throw new InvalidArgumentException("Canceled loans cannot be returned");
    }

    // Begin a database transaction
    DB::beginTransaction();

    try {
      // Set return date if not provided
      if (empty($returnData['return_date'])) {
        $returnData['return_date'] = Carbon::now();
      }

      // Add return notes if provided
      if (!empty($returnData['notes'])) {
        $notes = $loan->notes ? $loan->notes . "\n\nReturn notes: " . $returnData['notes'] : "Return notes: " . $returnData['notes'];
        $loan->notes = $notes;
      }

      // Update loan with condition tags and return notes
      $loan->condition_tags = $returnData['condition_tags'] ?? [];
      $loan->return_notes = $returnData['return_notes'] ?? '';
      $loan->status = 'returned';
      $loan->return_date = $returnData['return_date'];
      $loan->save();

      // Update all associated items statuses in the pivot table
      $loan->items()->updateExistingPivot(
        $loan->items->pluck('id')->toArray(),
        ['status' => 'returned']
      );

      // For each item, check if it's in any other active loans
      // If not, set it back to 'available'
      foreach ($loan->items as $item) {
        // Update condition_after if provided for this item
        if (!empty($returnData['items']) && !empty($returnData['items'][$item->id]['condition_after'])) {
          $loan->items()->updateExistingPivot(
            $item->id,
            ['condition_after' => $returnData['items'][$item->id]['condition_after']]
          );
        }

        $stillOnLoan = $item->loans()
          ->where('loans.id', '!=', $loan->id)
          ->whereIn('loans.status', ['active', 'pending', 'overdue'])
          ->exists();

        if (!$stillOnLoan) {
          // Check if item is damaged based on return data
          $newStatus = 'available';

          if (!empty($returnData['items']) && !empty($returnData['items'][$item->id]['status'])) {
            $itemReturnStatus = $returnData['items'][$item->id]['status'];
            if (in_array($itemReturnStatus, ['damaged', 'lost'])) {
              $newStatus = $itemReturnStatus;
            }
          }

          $item->update(['status' => $newStatus]);
        }
      }

      // Commit the transaction
      DB::commit();

      return $loan->fresh();
    } catch (Throwable $e) {
      // Rollback the transaction on failure
      DB::rollBack();

      // Log the error
      Log::error('Loan return failed', [
        'loan_id' => $loan->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      throw $e;
    }
  }

  /**
   * Update an existing loan with the given data
   *
   * @param Loan $loan The loan to update
   * @param array $data The updated loan data
   * @return Loan The updated loan
   * @throws LoanCreationException If loan update fails
   */
  public function updateLoan(Loan $loan, array $data): Loan
  {
    // Validate that we have borrower information
    $this->validateBorrowerData($data);

    // Extract items data before updating the loan
    $items = $data['items'] ?? [];
    unset($data['items']);

    // Handle borrower type transitions and updates
    if (isset($data['borrower_type'])) {
      // If borrower type is changing from GuestBorrower to User
      if ($data['borrower_type'] === 'App\\Models\\User' && $loan->borrower_type === 'App\\Models\\GuestBorrower') {
        // We don't delete the guest borrower record, just change the association
        // Ensure borrower_id is set
        if (empty($data['borrower_id'])) {
          throw LoanCreationException::invalidBorrower('User ID is required when changing to a registered user');
        }
      }
      // If borrower type is changing from User to GuestBorrower
      else if ($data['borrower_type'] === 'App\\Models\\GuestBorrower' && $loan->borrower_type === 'App\\Models\\User') {
        // Create a new guest borrower
        $data = $this->processGuestBorrower($data);
      }
      // Same type but it's a GuestBorrower - handle update
      else if ($this->isGuestBorrower($data)) {
        if ($loan->borrower_type === 'App\\Models\\GuestBorrower' && $loan->borrower) {
          // Update existing guest borrower
          $guestData = [
            'name' => $data['guest_name'] ?? null,
            'email' => $data['guest_email'] ?? null,
            'phone' => $data['guest_phone'] ?? null,
            'id_number' => $data['guest_id_number'] ?? null,
            'organization' => $data['guest_organization'] ?? null,
          ];

          // Remove guest data from loan record
          unset(
            $data['guest_name'],
            $data['guest_email'],
            $data['guest_phone'],
            $data['guest_id_number'],
            $data['guest_organization']
          );

          $loan->borrower->update($guestData);
        } else {
          // Create a new guest borrower
          $data = $this->processGuestBorrower($data);
        }
      }
    }

    // Begin a database transaction
    DB::beginTransaction();

    try {
      // Get previously attached items before detaching
      $previousItems = $loan->items()->pluck('items.id')->toArray();

      // Check if important fields that would affect the voucher have changed
      $shouldRegenerateVoucher = false;
      $voucherAffectingFields = ['borrower_id', 'borrower_type', 'loan_date', 'due_date'];
      foreach ($voucherAffectingFields as $field) {
        if (isset($data[$field]) && $data[$field] != $loan->$field) {
          $shouldRegenerateVoucher = true;
          break;
        }
      }

      // Update the loan record
      $loan->update($data);

      // Only manage items if they are in the request
      if (isset($items)) {
        // Items changing also triggers voucher regeneration
        $shouldRegenerateVoucher = true;

        // Clear existing items to avoid duplicates
        $loan->items()->detach();

        // Reset status of previously attached items that are not in the new list
        $newItemIds = collect($items)->pluck('item_id')->filter()->toArray();
        $this->itemModel::whereIn('id', $previousItems)
          ->whereNotIn('id', $newItemIds)
          ->where('status', 'borrowed')
          ->update(['status' => 'available']);

        // If we have items, validate them and assign to the loan
        if (!empty($items)) {
          $this->validateAndAssignItems($loan, $items);
        }
      }

      // Commit the transaction
      DB::commit();

      // Regenerate the voucher if needed
      if ($shouldRegenerateVoucher) {
        $this->generateVoucher($loan->fresh());
      }

      return $loan->fresh();
    } catch (Throwable $e) {
      // Rollback the transaction on failure
      DB::rollBack();

      // Log the error
      Log::error('Loan update failed', [
        'loan_id' => $loan->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $data
      ]);

      // Re-throw as a LoanCreationException
      if ($e instanceof LoanCreationException) {
        throw $e;
      }

      throw new LoanCreationException(
        'Failed to update loan: ' . $e->getMessage(),
        is_numeric($e->getCode()) ? (int) $e->getCode() : 0,
        $e
      );
    }
  }
}
