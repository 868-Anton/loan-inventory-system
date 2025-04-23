<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Create guest borrowers from existing guest loans
        $guestLoans = DB::table('loans')
            ->where('is_guest', true)
            ->whereNotNull('guest_name')
            ->get();

        foreach ($guestLoans as $loan) {
            // Create a new guest borrower record
            $guestBorrowerId = DB::table('guest_borrowers')->insertGetId([
                'name' => $loan->guest_name,
                'email' => $loan->guest_email,
                'phone' => $loan->guest_phone,
                'id_number' => $loan->guest_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update the loan to use the new polymorphic relationship
            DB::table('loans')
                ->where('id', $loan->id)
                ->update([
                    'borrower_type' => 'App\\Models\\GuestBorrower',
                    'borrower_id' => $guestBorrowerId,
                ]);
        }

        // Step 2: Update all regular user loans to use the polymorphic relationship
        DB::table('loans')
            ->whereNull('borrower_type')
            ->whereNotNull('user_id')
            ->update([
                'borrower_type' => 'App\\Models\\User',
                'borrower_id' => DB::raw('user_id'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data migration, and we can't easily reverse it
        // We'll do our best to restore the previous state

        // Step 1: Find all loans with GuestBorrower relationship
        $guestLoans = DB::table('loans')
            ->where('borrower_type', 'App\\Models\\GuestBorrower')
            ->whereNotNull('borrower_id')
            ->get();

        foreach ($guestLoans as $loan) {
            // Get guest borrower data
            $guestBorrower = DB::table('guest_borrowers')
                ->where('id', $loan->borrower_id)
                ->first();

            if ($guestBorrower) {
                // Update the loan with guest information
                DB::table('loans')
                    ->where('id', $loan->id)
                    ->update([
                        'is_guest' => true,
                        'guest_name' => $guestBorrower->name,
                        'guest_email' => $guestBorrower->email,
                        'guest_phone' => $guestBorrower->phone,
                        'guest_id' => $guestBorrower->id_number,
                    ]);
            }
        }

        // Step 2: Reset the polymorphic fields for all loans
        DB::table('loans')
            ->whereNotNull('borrower_type')
            ->update([
                'borrower_type' => null,
                'borrower_id' => null,
            ]);
    }
};
