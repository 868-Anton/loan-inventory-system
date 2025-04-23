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
            // Add borrower_type column if it doesn't exist
            if (!Schema::hasColumn('loans', 'borrower_type')) {
                $table->string('borrower_type')->nullable()->after('notes');
            }

            // Add borrower_id column if it doesn't exist
            if (!Schema::hasColumn('loans', 'borrower_id')) {
                $table->unsignedBigInteger('borrower_id')->nullable()->after('borrower_type');
            }

            // Add index for faster queries if it doesn't exist
            if (!Schema::hasIndex('loans', ['borrower_type', 'borrower_id'])) {
                $table->index(['borrower_type', 'borrower_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Drop index and columns if they exist
            if (Schema::hasIndex('loans', ['borrower_type', 'borrower_id'])) {
                $table->dropIndex(['borrower_type', 'borrower_id']);
            }

            if (Schema::hasColumn('loans', 'borrower_id')) {
                $table->dropColumn('borrower_id');
            }

            if (Schema::hasColumn('loans', 'borrower_type')) {
                $table->dropColumn('borrower_type');
            }
        });
    }
};
