<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  /**
   * Define the application's command schedule.
   */
  protected function schedule(Schedule $schedule): void
  {
    // Daily sync of item statuses to fix any inconsistencies
    $schedule->command('items:sync-status')->daily();

    // Run the database validation check daily
    $schedule->command('db:check')->daily();

    // Run the item status normalization command daily to prevent inconsistencies
    $schedule->command('items:normalize-status')->daily();
  }

  /**
   * Register the commands for the application.
   */
  protected function commands(): void
  {
    $this->load(__DIR__ . '/Commands');

    require base_path('routes/console.php');
  }
}
