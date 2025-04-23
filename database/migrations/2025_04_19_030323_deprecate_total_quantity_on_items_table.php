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
        Schema::table('items', function (Blueprint $table) {
            // Rename total_quantity to deprecated_total_quantity to maintain backward compatibility
            $table->renameColumn('total_quantity', 'deprecated_total_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Revert the renaming if migration is rolled back
            $table->renameColumn('deprecated_total_quantity', 'total_quantity');
        });
    }
};
