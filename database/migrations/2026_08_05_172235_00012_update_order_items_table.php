<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->modifyColumn('selected_attributes', text()->nullable()->default(''));
            $table->modifyColumn('created_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('updated_at', timestamp()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->modifyColumn('selected_attributes', json()->nullable()->default(''));
            $table->modifyColumn('created_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('updated_at', timestamp()->nullable()->default(''));
        });
    }
};