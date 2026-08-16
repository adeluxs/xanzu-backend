<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bnpl_checkout_sessions')) {
            Schema::create('bnpl_checkout_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('token', 191)->unique();
                $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
                // Orders are created by a later imported-schema migration on a
                // fresh install, so keep this relationship indexed without an
                // early foreign-key dependency.
                $table->foreignId('order_id')->nullable()->index();
                $table->string('merchant_public_key', 191)->index();
                $table->string('merchant_order_id', 191)->index();
                $table->string('merchant_reference_id', 191)->nullable()->index();
                $table->string('platform', 100)->nullable();
                $table->string('status', 50)->default('pending')->index();
                $table->string('merchant_status', 50)->nullable();
                $table->decimal('amount', 16, 2);
                $table->string('currency', 20)->nullable();
                $table->json('customer')->nullable();
                $table->json('items')->nullable();
                $table->json('payload');
                $table->text('success_url')->nullable();
                $table->text('callback_url')->nullable();
                $table->text('webhook_url')->nullable();
                $table->text('cancel_url')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['merchant_id', 'merchant_order_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bnpl_checkout_sessions');
    }
};
