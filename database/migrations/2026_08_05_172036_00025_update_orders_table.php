<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->modifyColumn('shipping_address', text()->nullable()->default(''));
            $table->modifyColumn('delivery_note', text()->nullable()->default(''));
            $table->modifyColumn('order_date', timestamp()->default(''));
            $table->modifyColumn('created_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('updated_at', timestamp()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->modifyColumn('shipping_address', text());
            $table->modifyColumn('delivery_note', text());
            $table->modifyColumn('order_date', timestamp()->default(''));
            $table->modifyColumn('created_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('updated_at', timestamp()->nullable()->default(''));
        });
    }
};