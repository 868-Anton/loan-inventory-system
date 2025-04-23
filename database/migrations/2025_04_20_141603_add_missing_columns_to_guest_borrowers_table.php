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
            // Add email column if it doesn't exist
            if (!Schema::hasColumn('guest_borrowers', 'email')) {
                $table->string('email')->nullable()->after('name');
            }

            // Add phone column if it doesn't exist
            if (!Schema::hasColumn('guest_borrowers', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            // Add id_number column if it doesn't exist
            if (!Schema::hasColumn('guest_borrowers', 'id_number')) {
                $table->string('id_number')->nullable()->after('phone');
            }

            // Add organization column if it doesn't exist
            if (!Schema::hasColumn('guest_borrowers', 'organization')) {
                $table->string('organization')->nullable()->after('id_number');
            }

            // Add notes column if it doesn't exist
            if (!Schema::hasColumn('guest_borrowers', 'notes')) {
                $table->text('notes')->nullable()->after('organization');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_borrowers', function (Blueprint $table) {
            // Drop columns if they exist
            $columns = ['email', 'phone', 'id_number', 'organization', 'notes'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('guest_borrowers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
