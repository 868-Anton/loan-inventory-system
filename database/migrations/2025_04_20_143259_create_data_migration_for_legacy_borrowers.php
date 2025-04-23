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
        // Only try to migrate if the right columns exist
        if (Schema::hasColumn('loans', 'guest_name') && Schema::hasColumn('guest_borrowers', 'name')) {
            // Step 1: Create guest borrowers from existing guest loans with guest data
            $guestLoans = DB::table('loans')
                ->whereNotNull('guest_name')
                ->get();

            foreach ($guestLoans as $loan) {
                // Create a new guest borrower record
                $guestBorrowerId = DB::table('guest_borrowers')->insertGetId([
                    'name' => $loan->guest_name,
                    'email' => $loan->guest_email ?? null,
                    'phone' => $loan->guest_phone ?? null,
                    'id_number' => $loan->guest_id ?? null,
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
        }

        // Step 2: Update all regular user loans to use the polymorphic relationship
        if (
            Schema::hasColumn('loans', 'user_id') &&
            Schema::hasColumn('loans', 'borrower_type') &&
            Schema::hasColumn('loans', 'borrower_id')
        ) {

            DB::table('loans')
                ->whereNull('borrower_type')
                ->whereNotNull('user_id')
                ->update([
                    'borrower_type' => 'App\\Models\\User',
                    'borrower_id' => DB::raw('user_id'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only attempt data restoration if columns exist
        if (
            Schema::hasColumn('loans', 'borrower_type') &&
            Schema::hasColumn('loans', 'borrower_id') &&
            Schema::hasColumn('loans', 'guest_name')
        ) {

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
                    $updateData = [
                        'guest_name' => $guestBorrower->name,
                    ];

                    if (Schema::hasColumn('loans', 'guest_email')) {
                        $updateData['guest_email'] = $guestBorrower->email;
                    }

                    if (Schema::hasColumn('loans', 'guest_phone')) {
                        $updateData['guest_phone'] = $guestBorrower->phone;
                    }

                    if (Schema::hasColumn('loans', 'guest_id')) {
                        $updateData['guest_id'] = $guestBorrower->id_number;
                    }

                    DB::table('loans')
                        ->where('id', $loan->id)
                        ->update($updateData);
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
    }
};
