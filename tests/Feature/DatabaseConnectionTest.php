<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DatabaseConnectionTest extends TestCase
{
  /**
   * Test basic database connectivity.
   */
  public function test_database_connection(): void
  {
    // Check if we can query the database directly
    $result = DB::select('SELECT 1 as test');
    $this->assertEquals(1, $result[0]->test);

    // Check if we can fetch migrations
    $migrations = DB::table('migrations')->get();
    $this->assertNotEmpty($migrations);

    // Output some information about the database state
    $this->info('Database connection established successfully');
    $this->info('Number of migrations: ' . count($migrations));

    // Check model counts
    $userCount = User::count();
    $categoryCount = Category::count();
    $itemCount = Item::count();
    $loanCount = Loan::count();

    $this->info('Users: ' . $userCount);
    $this->info('Categories: ' . $categoryCount);
    $this->info('Items: ' . $itemCount);
    $this->info('Loans: ' . $loanCount);
  }

  /**
   * Custom method to output information in the test.
   */
  protected function info($message): void
  {
    fwrite(STDOUT, $message . "\n");
  }
}
