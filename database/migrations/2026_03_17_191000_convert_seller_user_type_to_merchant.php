<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'user_type')) {
            DB::table('users')->where('user_type', 'seller')->update(['user_type' => 'merchant']);

            DB::statement("ALTER TABLE users MODIFY COLUMN user_type ENUM('merchant','buyer') NOT NULL DEFAULT 'buyer'");
        }

        if (Schema::hasTable('kycs') && Schema::hasColumn('kycs', 'user_type')) {
            DB::table('kycs')->where('user_type', 'seller')->update(['user_type' => 'merchant']);

            DB::statement("ALTER TABLE kycs MODIFY COLUMN user_type ENUM('buyer','merchant','both') NOT NULL DEFAULT 'both'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'user_type')) {
            DB::table('users')->where('user_type', 'merchant')->update(['user_type' => 'seller']);

            DB::statement("ALTER TABLE users MODIFY COLUMN user_type ENUM('seller','buyer') NOT NULL DEFAULT 'buyer'");
        }

        if (Schema::hasTable('kycs') && Schema::hasColumn('kycs', 'user_type')) {
            DB::table('kycs')->where('user_type', 'merchant')->update(['user_type' => 'seller']);

            DB::statement("ALTER TABLE kycs MODIFY COLUMN user_type ENUM('buyer','seller','both') NOT NULL DEFAULT 'both'");
        }
    }
};
