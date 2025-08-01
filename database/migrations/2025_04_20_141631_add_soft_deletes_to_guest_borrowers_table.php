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
        Schema::table('guest_borrowers', function (Blueprint $table) {
            if (!Schema::hasColumn('guest_borrowers', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_borrowers', function (Blueprint $table) {
            if (Schema::hasColumn('guest_borrowers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
