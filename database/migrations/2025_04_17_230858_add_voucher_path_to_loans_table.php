<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Check if voucher_path column doesn't exist before adding it
            if (!Schema::hasColumn('loans', 'voucher_path')) {
                // Add the voucher_path column to store the path to the loan voucher PDF
                $table->string('voucher_path')->nullable()->after('signature');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Remove the column if rolling back
            if (Schema::hasColumn('loans', 'voucher_path')) {
                $table->dropColumn('voucher_path');
            }
        });
    }
};
