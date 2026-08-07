<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_limits', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['buyer', 'merchant', 'all'])->default('all');
            $table->decimal('min_amount', 15, 2)->default(0.00);
            $table->decimal('max_amount', 15, 2)->default(0.00);
            $table->decimal('daily_limit', 15, 2)->default(0.00);
            $table->integer('daily_transaction_count')->default(0);
            $table->decimal('monthly_limit', 15, 2)->default(0.00);
            $table->integer('monthly_transaction_count')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->index(['user_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_limits');
    }
};
