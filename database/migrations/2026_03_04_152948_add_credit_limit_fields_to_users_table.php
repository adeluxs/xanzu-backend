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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('current_credit_limit_id')->nullable()->after('id');
            $table->decimal('credit_limit_amount', 15, 2)->default(0)->after('current_credit_limit_id');
            $table->decimal('used_credit_limit_amount', 15, 2)->default(0)->after('credit_limit_amount');
            $table->decimal('remaining_credit_limit_amount', 15, 2)->default(0)->after('used_credit_limit_amount');

            $table->foreign('current_credit_limit_id')
                ->references('id')
                ->on('credit_limits')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_credit_limit_id']);
            $table->dropColumn([
                'current_credit_limit_id',
                'credit_limit_amount',
                'used_credit_limit_amount',
                'remaining_credit_limit_amount',
            ]);
        });
    }
};
