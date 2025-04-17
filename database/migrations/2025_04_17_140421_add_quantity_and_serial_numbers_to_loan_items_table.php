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
            $table->unsignedInteger('quantity')->default(1)->after('item_id');
            $table->json('serial_numbers')->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'serial_numbers']);
        });
    }
};
