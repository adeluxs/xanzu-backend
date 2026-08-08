<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone')
            && ! Schema::hasIndex('users', 'users_phone_transfer_idx')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('phone', 'users_phone_transfer_idx');
            });
        }

        if (Schema::hasTable('transactions')
            && ! Schema::hasIndex('transactions', 'txn_transfer_usage_idx')) {
            Schema::table('transactions', function (Blueprint $table) {
                // Transfer-limit summaries are scoped by user/type/status and a
                // monthly created_at window. This composite index avoids four
                // broad transaction scans when the mobile transfer page opens.
                $table->index(
                    ['user_id', 'type', 'status', 'created_at'],
                    'txn_transfer_usage_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasIndex('users', 'users_phone_transfer_idx')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropIndex('users_phone_transfer_idx'));
        }

        if (Schema::hasTable('transactions') && Schema::hasIndex('transactions', 'txn_transfer_usage_idx')) {
            Schema::table('transactions', fn (Blueprint $table) => $table->dropIndex('txn_transfer_usage_idx'));
        }
    }
};
