<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transfer_limits')) {
            return;
        }

        if (! DB::table('transfer_limits')->where('user_type', 'all')->exists()) {
            DB::table('transfer_limits')->insert([
                'user_type' => 'all',
                'min_amount' => 0,
                'max_amount' => 0,
                'daily_limit' => 0,
                'daily_transaction_count' => 0,
                'monthly_limit' => 0,
                'monthly_transaction_count' => 0,
                'status' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Preserve administrator configuration on rollback.
    }
};
