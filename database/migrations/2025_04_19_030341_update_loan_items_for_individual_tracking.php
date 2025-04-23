<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            // Rename quantity to deprecated_quantity for backwards compatibility
            $table->renameColumn('quantity', 'deprecated_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            // Revert the column renaming
            $table->renameColumn('deprecated_quantity', 'quantity');
        });
    }
};
