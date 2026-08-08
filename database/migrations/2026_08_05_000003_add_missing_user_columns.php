<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'transfer_status')) {
                $table->boolean('transfer_status')->default(1)->after('withdraw_status');
            }
            if (!Schema::hasColumn('users', 'transfer_kyc_verified')) {
                $table->boolean('transfer_kyc_verified')->default(0)->after('transfer_status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'transfer_status')) {
                $table->dropColumn('transfer_status');
            }
            if (Schema::hasColumn('users', 'transfer_kyc_verified')) {
                $table->dropColumn('transfer_kyc_verified');
            }
        });
    }
};
