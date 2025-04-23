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
            // After successfully migrating data to the new polymorphic relationship,
            // we can drop the old guest-related columns
            if (Schema::hasColumn('loans', 'is_guest')) {
                $table->dropColumn('is_guest');
            }
            if (Schema::hasColumn('loans', 'guest_name')) {
                $table->dropColumn('guest_name');
            }
            if (Schema::hasColumn('loans', 'guest_email')) {
                $table->dropColumn('guest_email');
            }
            if (Schema::hasColumn('loans', 'guest_phone')) {
                $table->dropColumn('guest_phone');
            }
            if (Schema::hasColumn('loans', 'guest_id')) {
                $table->dropColumn('guest_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // We can't really restore the data, but we can add the columns back
            $table->boolean('is_guest')->default(false)->after('user_id');
            $table->string('guest_name')->nullable()->after('is_guest');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_phone')->nullable()->after('guest_email');
            $table->string('guest_id')->nullable()->after('guest_phone');
        });
    }
};
