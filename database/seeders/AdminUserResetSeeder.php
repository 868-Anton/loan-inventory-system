<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserResetSeeder extends Seeder
{
  /**
   * Run the database seeds to create a new admin user.
   */
  public function run(): void
  {
    User::create([
      'name' => 'Admin',
      'email' => 'new.admin@example.com',
      'password' => Hash::make('password'),
    ]);
  }
}
