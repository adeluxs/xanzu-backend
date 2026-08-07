<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'current_credit_limit_id')) {
                $table->unsignedBigInteger('current_credit_limit_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'credit_limit_amount')) {
                $table->decimal('credit_limit_amount', 15, 2)->default(0)->after('current_credit_limit_id');
            }
            if (!Schema::hasColumn('users', 'used_credit_limit_amount')) {
                $table->decimal('used_credit_limit_amount', 15, 2)->default(0)->after('credit_limit_amount');
            }
            if (!Schema::hasColumn('users', 'remaining_credit_limit_amount')) {
                $table->decimal('remaining_credit_limit_amount', 15, 2)->default(0)->after('used_credit_limit_amount');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'current_credit_limit_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'current_credit_limit_id',
                    'credit_limit_amount',
                    'used_credit_limit_amount',
                    'remaining_credit_limit_amount',
                ]);
            });
        }
    }
};
