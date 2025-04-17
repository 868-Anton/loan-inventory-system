<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeItemStatus extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'items:normalize-status';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Normalize item statuses and fix inconsistencies between items and loans';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Starting item status normalization...');

    // Step 1: Normalize all statuses to lowercase
    $this->info('Normalizing all item statuses to lowercase...');
    $itemsUpdated = 0;

    Item::chunk(100, function ($items) use (&$itemsUpdated) {
      foreach ($items as $item) {
        if ($item->status) {
          $oldStatus = $item->status;
          $item->status = strtolower($oldStatus);

          if ($oldStatus !== $item->status) {
            $item->save();
            $itemsUpdated++;
          }
        }
      }
    });

    $this->info("Normalized {$itemsUpdated} item statuses to lowercase.");

    // Step 2: Fix inconsistencies between loans and items
    $this->info('Fixing inconsistencies between loans and items...');

    // Find active loans
    $activeLoans = Loan::whereIn('status', ['active', 'pending', 'overdue'])
      ->whereNull('return_date')
      ->get();

    $this->info("Found {$activeLoans->count()} active loans to process.");

    $itemsFixed = 0;
    foreach ($activeLoans as $loan) {
      foreach ($loan->items as $item) {
        if ($item->status !== 'borrowed') {
          $item->status = 'borrowed';
          $item->save();
          $itemsFixed++;
        }
      }
    }

    $this->info("Updated {$itemsFixed} items to 'borrowed' status based on active loans.");

    // Step 3: Find items without active loans and mark them as available
    $this->info('Finding items without active loans to mark as available...');

    $itemsWithoutLoans = Item::where('status', 'borrowed')
      ->whereDoesntHave('loans', function ($query) {
        $query->whereIn('loans.status', ['active', 'pending', 'overdue']);
      })
      ->get();

    $availableFixed = 0;
    foreach ($itemsWithoutLoans as $item) {
      $item->status = 'available';
      $item->save();
      $availableFixed++;
    }

    $this->info("Updated {$availableFixed} items to 'available' status that were incorrectly marked as borrowed.");

    $this->info('Item status normalization completed successfully!');

    return 0;
  }
}
