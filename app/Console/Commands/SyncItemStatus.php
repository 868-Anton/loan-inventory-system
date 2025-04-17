<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class SyncItemStatus extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'items:sync-status';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Synchronize item statuses based on their loan status';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Synchronizing item statuses with loan status...');

    // Step 1: Reset all items to available
    $this->info('Resetting all items to available status...');
    Item::where('status', 'borrowed')->update(['status' => 'available']);

    // Step 2: Find all items in active loans
    $this->info('Finding items in active loans...');
    $activeLoans = Loan::whereIn('status', ['active', 'pending', 'overdue'])->get();
    $this->info("Found {$activeLoans->count()} active/pending/overdue loans");

    // Step 3: Mark all items in active loans as borrowed
    $itemsUpdated = 0;

    foreach ($activeLoans as $loan) {
      $items = $loan->items;
      foreach ($items as $item) {
        $item->update(['status' => 'borrowed']);
        $itemsUpdated++;
        $this->line("- Updated {$item->name} (ID: {$item->id}) to borrowed status");
      }
    }

    $this->info("Sync complete! Updated {$itemsUpdated} items to borrowed status.");

    return 0;
  }
}
