<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
  protected $signature = 'users:list';
  protected $description = 'List all users in the system';

  public function handle()
  {
    $users = User::all(['id', 'name', 'email']);

    $this->table(
      ['ID', 'Name', 'Email'],
      $users->toArray()
    );

    return Command::SUCCESS;
  }
}
