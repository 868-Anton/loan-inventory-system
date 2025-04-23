<?php

namespace Tests\Unit;

use App\Exceptions\LoanCreationException;
use App\Models\GuestBorrower;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
  use RefreshDatabase;

  /**
   * @var LoanService
   */
  private LoanService $loanService;

  /**
   * @var Dispatcher|\PHPUnit\Framework\MockObject\MockObject
   */
  private $eventDispatcherMock;

  public function setUp(): void
  {
    parent::setUp();

    // Create mock event dispatcher
    $this->eventDispatcherMock = $this->createMock(Dispatcher::class);

    // Set up the service with real models and mock event dispatcher
    $this->loanService = new LoanService(
      new Loan(),
      new Item(),
      $this->eventDispatcherMock
    );

    // Seed the database with test data
    $this->seedTestData();
  }

  /**
   * Test creating a loan with a registered user borrower.
   */
  public function testCreateLoanWithRegisteredUser(): void
  {
    // Find a user to use as borrower
    $user = User::first();

    // Find an available item
    $item = Item::where('status', 'available')->first();

    // Prepare loan data
    $loanData = [
      'loan_number' => 'LOAN-' . mt_rand(1000, 9999),
      'borrower_type' => 'App\\Models\\User',
      'borrower_id' => $user->id,
      'loan_date' => Carbon::now()->format('Y-m-d'),
      'due_date' => Carbon::now()->addMonth()->format('Y-m-d'),
      'status' => 'active',
      'items' => [
        [
          'item_id' => $item->id,
          'deprecated_quantity' => 1,
          'condition_before' => 'Good condition',
        ]
      ]
    ];

    // Create the loan
    $loan = $this->loanService->createLoan($loanData);

    // Assert loan was created
    $this->assertInstanceOf(Loan::class, $loan);
    $this->assertEquals('App\\Models\\User', $loan->borrower_type);
    $this->assertEquals($user->id, $loan->borrower_id);
    $this->assertEquals('active', $loan->status);

    // Assert item was attached to loan
    $this->assertEquals(1, $loan->items->count());
    $this->assertEquals($item->id, $loan->items->first()->id);

    // Assert item status was updated
    $this->assertEquals('borrowed', $item->fresh()->status);
  }

  /**
   * Test creating a loan with a guest borrower.
   */
  public function testCreateLoanWithGuestBorrower(): void
  {
    // Find an available item
    $item = Item::where('status', 'available')->first();

    // Ensure we have a different item than the first test
    if (!$item) {
      $this->fail('No available items for testing');
    }

    // Prepare loan data with guest borrower
    $loanData = [
      'loan_number' => 'LOAN-' . mt_rand(1000, 9999),
      'borrower_type' => 'App\\Models\\GuestBorrower',
      'guest_name' => 'Test Guest',
      'guest_email' => 'guest@example.com',
      'guest_phone' => '555-1234',
      'guest_id_number' => 'G-12345',
      'loan_date' => Carbon::now()->format('Y-m-d'),
      'due_date' => Carbon::now()->addMonth()->format('Y-m-d'),
      'status' => 'active',
      'items' => [
        [
          'item_id' => $item->id,
          'deprecated_quantity' => 1,
          'condition_before' => 'Good condition',
        ]
      ]
    ];

    // Create the loan
    $loan = $this->loanService->createLoan($loanData);

    // Assert loan was created
    $this->assertInstanceOf(Loan::class, $loan);
    $this->assertEquals('App\\Models\\GuestBorrower', $loan->borrower_type);

    // Assert guest borrower was created
    $guestBorrower = GuestBorrower::where('name', 'Test Guest')->first();
    $this->assertNotNull($guestBorrower);
    $this->assertEquals('guest@example.com', $guestBorrower->email);
    $this->assertEquals($guestBorrower->id, $loan->borrower_id);

    // Assert item was attached to loan
    $this->assertEquals(1, $loan->items->count());
    $this->assertEquals($item->id, $loan->items->first()->id);

    // Assert item status was updated
    $this->assertEquals('borrowed', $item->fresh()->status);
  }

  /**
   * Test creating a loan with invalid borrower data.
   */
  public function testCreateLoanWithInvalidBorrowerThrowsException(): void
  {
    // Prepare loan data with missing borrower ID
    $loanData = [
      'loan_number' => 'LOAN-' . mt_rand(1000, 9999),
      'borrower_type' => 'App\\Models\\User',
      // Missing borrower_id
      'loan_date' => Carbon::now()->format('Y-m-d'),
      'due_date' => Carbon::now()->addMonth()->format('Y-m-d'),
      'status' => 'active',
    ];

    // Expect exception
    $this->expectException(LoanCreationException::class);
    $this->expectExceptionCode(LoanCreationException::ERROR_INVALID_BORROWER);

    // This should throw an exception
    $this->loanService->createLoan($loanData);
  }

  /**
   * Test creating a loan with invalid guest borrower data.
   */
  public function testCreateLoanWithInvalidGuestBorrowerThrowsException(): void
  {
    // Prepare loan data with missing guest name
    $loanData = [
      'loan_number' => 'LOAN-' . mt_rand(1000, 9999),
      'borrower_type' => 'App\\Models\\GuestBorrower',
      // Missing guest_name
      'guest_email' => 'guest@example.com',
      'loan_date' => Carbon::now()->format('Y-m-d'),
      'due_date' => Carbon::now()->addMonth()->format('Y-m-d'),
      'status' => 'active',
    ];

    // Expect exception
    $this->expectException(LoanCreationException::class);
    $this->expectExceptionCode(LoanCreationException::ERROR_MISSING_REQUIRED_DATA);

    // This should throw an exception
    $this->loanService->createLoan($loanData);
  }

  /**
   * Test that we can't borrow more items than available.
   */
  public function testInsufficientQuantityThrowsException(): void
  {
    // Find a user to use as borrower
    $user = User::first();

    // Find an available item
    $item = Item::where('status', 'available')->first();

    // Update the item to have a specific available quantity
    $item->update([
      'total_quantity' => 2,
    ]);

    // Prepare loan data with excessive quantity
    $loanData = [
      'loan_number' => 'LOAN-' . mt_rand(1000, 9999),
      'borrower_type' => 'App\\Models\\User',
      'borrower_id' => $user->id,
      'loan_date' => Carbon::now()->format('Y-m-d'),
      'due_date' => Carbon::now()->addMonth()->format('Y-m-d'),
      'status' => 'active',
      'items' => [
        [
          'item_id' => $item->id,
          'deprecated_quantity' => 5, // More than available
          'condition_before' => 'Good condition',
        ]
      ]
    ];

    // Expect exception
    $this->expectException(LoanCreationException::class);
    $this->expectExceptionCode(LoanCreationException::ERROR_INSUFFICIENT_QUANTITY);

    // This should throw an exception
    $this->loanService->createLoan($loanData);
  }

  /**
   * Test returning a loan.
   */
  public function testReturnLoan(): void
  {
    // Create a loan first
    $user = User::first();
    $item = Item::where('status', 'available')->first();

    $loanData = [
      'loan_number' => 'LOAN-' . mt_rand(1000, 9999),
      'borrower_type' => 'App\\Models\\User',
      'borrower_id' => $user->id,
      'loan_date' => Carbon::now()->format('Y-m-d'),
      'due_date' => Carbon::now()->addMonth()->format('Y-m-d'),
      'status' => 'active',
      'items' => [
        [
          'item_id' => $item->id,
          'deprecated_quantity' => 1,
          'condition_before' => 'Good condition',
        ]
      ]
    ];

    $loan = $this->loanService->createLoan($loanData);

    // Now return the loan
    $returnData = [
      'notes' => 'Returned in good condition',
      'items' => [
        $item->id => [
          'condition_after' => 'Still in good condition',
          'status' => 'available'
        ]
      ]
    ];

    $returnedLoan = $this->loanService->returnLoan($loan, $returnData);

    // Assert loan was returned
    $this->assertEquals('returned', $returnedLoan->status);
    $this->assertNotNull($returnedLoan->return_date);
    $this->assertStringContainsString('Returned in good condition', $returnedLoan->notes);

    // Assert item was updated
    $this->assertEquals('available', $item->fresh()->status);

    // Assert the pivot data was updated
    $pivotData = $returnedLoan->items->first()->pivot;
    $this->assertEquals('returned', $pivotData->status);
    $this->assertEquals('Still in good condition', $pivotData->condition_after);
  }

  /**
   * Test returning a damaged item.
   */
  public function testReturnDamagedItem(): void
  {
    // Create a loan first
    $user = User::first();
    $item = Item::where('status', 'available')->first();

    $loanData = [
      'loan_number' => 'LOAN-' . mt_rand(1000, 9999),
      'borrower_type' => 'App\\Models\\User',
      'borrower_id' => $user->id,
      'loan_date' => Carbon::now()->format('Y-m-d'),
      'due_date' => Carbon::now()->addMonth()->format('Y-m-d'),
      'status' => 'active',
      'items' => [
        [
          'item_id' => $item->id,
          'deprecated_quantity' => 1,
          'condition_before' => 'Good condition',
        ]
      ]
    ];

    $loan = $this->loanService->createLoan($loanData);

    // Now return the loan with damaged item
    $returnData = [
      'notes' => 'Item returned with damage',
      'items' => [
        $item->id => [
          'condition_after' => 'Screen is cracked',
          'status' => 'damaged'
        ]
      ]
    ];

    $returnedLoan = $this->loanService->returnLoan($loan, $returnData);

    // Assert loan was returned
    $this->assertEquals('returned', $returnedLoan->status);

    // Assert item was marked as damaged
    $this->assertEquals('damaged', $item->fresh()->status);
  }

  /**
   * Seed the database with test data.
   */
  private function seedTestData(): void
  {
    // Create a test user
    User::factory()->create([
      'name' => 'Test User',
      'email' => 'test@example.com',
    ]);

    // Create a couple of test items
    Item::create([
      'name' => 'Test Item 1',
      'status' => 'available',
      'total_quantity' => 5,
    ]);

    Item::create([
      'name' => 'Test Item 2',
      'status' => 'available',
      'total_quantity' => 3,
    ]);
  }
}
