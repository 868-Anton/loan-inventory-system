<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabase extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'db:check';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Check database connectivity and model relationships';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Checking database connection and schema...');

    // Test basic connectivity
    try {
      $result = DB::select('SELECT 1 as test');
      $this->info("✓ Basic connectivity: OK");
    } catch (\Exception $e) {
      $this->error("✗ Basic connectivity failed: " . $e->getMessage());
      return 1;
    }

    // Check database tables
    $requiredTables = ['users', 'categories', 'items', 'loans', 'loan_items', 'departments'];
    foreach ($requiredTables as $table) {
      if (Schema::hasTable($table)) {
        $this->info("✓ Table '{$table}' exists");
      } else {
        $this->error("✗ Table '{$table}' does not exist!");
      }
    }

    // Check model counts
    $this->info("\nCounting records in models:");
    $userCount = User::count();
    $this->info("Users: {$userCount}");

    $categoryCount = Category::count();
    $this->info("Categories: {$categoryCount}");

    $itemCount = Item::count();
    $this->info("Items: {$itemCount}");

    $loanCount = Loan::count();
    $this->info("Loans: {$loanCount}");

    $deptCount = Department::count();
    $this->info("Departments: {$deptCount}");

    // Check relationships
    $this->info("\nChecking relationships:");

    if ($categoryCount > 0) {
      $category = Category::first();
      $categoryItems = $category->items()->count();
      $this->info("- First category has {$categoryItems} items");
    }

    if ($itemCount > 0) {
      $item = Item::first();
      if ($item->category) {
        $this->info("- First item belongs to category: " . $item->category->name);
      } else {
        $this->info("- First item has no category");
      }

      // Check borrowed items
      $borrowedItems = Item::where('status', 'borrowed')->get();
      $this->info("\nBorrowed Items: " . $borrowedItems->count());
      foreach ($borrowedItems as $index => $borrowedItem) {
        if ($index < 5) { // Limit to first 5 to avoid excessive output
          $this->info("- {$borrowedItem->name} (ID: {$borrowedItem->id})");
          $activeLoans = $borrowedItem->loans()
            ->whereIn('loans.status', ['active', 'pending', 'overdue'])
            ->get();

          foreach ($activeLoans as $loanIndex => $loan) {
            if ($loanIndex < 3) { // Limit to first 3 loans
              $this->info("  - In loan #{$loan->id}: {$loan->loan_number} (Status: {$loan->status})");
              $pivotData = $loan->items()->where('item_id', $borrowedItem->id)->first()->pivot;
              $this->info("    - Quantity: {$pivotData->quantity}, Pivot Status: {$pivotData->status}");
            }
          }
        }
      }
    }

    // Check active loans and their items
    $this->info("\nActive Loans and Their Items:");
    $activeLoans = Loan::whereIn('status', ['active', 'pending', 'overdue'])->get();
    $this->info("Active/Pending/Overdue Loans: " . $activeLoans->count());

    foreach ($activeLoans as $index => $loan) {
      if ($index < 3) { // Limit to first 3 to avoid excessive output
        $this->info("- Loan #{$loan->id}: {$loan->loan_number} (Status: {$loan->status})");
        $loanItems = $loan->items;
        $this->info("  - Contains {$loanItems->count()} items:");

        foreach ($loanItems as $itemIndex => $item) {
          if ($itemIndex < 3) { // Limit to first 3 items
            $this->info("    - {$item->name} (Item Status: {$item->status}, Pivot Status: {$item->pivot->status})");
          }
        }
      }
    }

    return 0;
  }
}
