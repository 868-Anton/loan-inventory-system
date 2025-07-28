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
            // Check if deprecated_quantity exists and quantity doesn't
            if (Schema::hasColumn('loan_items', 'deprecated_quantity') && !Schema::hasColumn('loan_items', 'quantity')) {
                // Rename deprecated_quantity to quantity
                $table->renameColumn('deprecated_quantity', 'quantity');
            } elseif (!Schema::hasColumn('loan_items', 'quantity')) {
                // If neither exists, add quantity column
                $table->unsignedInteger('quantity')->default(1)->after('item_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            if (Schema::hasColumn('loan_items', 'quantity') && !Schema::hasColumn('loan_items', 'deprecated_quantity')) {
                $table->renameColumn('quantity', 'deprecated_quantity');
            }
        });
    }
};
