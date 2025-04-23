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
            // Add polymorphic relationship columns
            $table->string('borrower_type')->nullable()->after('user_id');
            $table->unsignedBigInteger('borrower_id')->nullable()->after('borrower_type');

            // Add index for faster queries
            $table->index(['borrower_type', 'borrower_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Drop index and columns
            $table->dropIndex(['borrower_type', 'borrower_id']);
            $table->dropColumn('borrower_id');
            $table->dropColumn('borrower_type');
        });
    }
};
