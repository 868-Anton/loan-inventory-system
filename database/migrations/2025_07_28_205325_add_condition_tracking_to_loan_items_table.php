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
        Schema::table('loan_items', function (Blueprint $table) {
            // Add new condition tracking columns that don't exist yet
            if (!Schema::hasColumn('loan_items', 'condition_tags')) {
                $table->json('condition_tags')->nullable()->after('returned_at');
            }
            if (!Schema::hasColumn('loan_items', 'return_notes')) {
                $table->text('return_notes')->nullable()->after('condition_tags');
            }
            if (!Schema::hasColumn('loan_items', 'returned_by')) {
                $table->string('returned_by')->nullable()->after('condition_after');
            }
            if (!Schema::hasColumn('loan_items', 'condition_assessed_at')) {
                $table->timestamp('condition_assessed_at')->nullable()->after('returned_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('loan_items', 'condition_tags')) {
                $columnsToDrop[] = 'condition_tags';
            }
            if (Schema::hasColumn('loan_items', 'return_notes')) {
                $columnsToDrop[] = 'return_notes';
            }
            if (Schema::hasColumn('loan_items', 'returned_by')) {
                $columnsToDrop[] = 'returned_by';
            }
            if (Schema::hasColumn('loan_items', 'condition_assessed_at')) {
                $columnsToDrop[] = 'condition_assessed_at';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
