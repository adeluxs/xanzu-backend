<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bnpl_checkout_sessions', function (Blueprint $table) {
            $table->modifyColumn('customer', text()->nullable()->default(''));
            $table->modifyColumn('items', text()->nullable()->default(''));
            $table->modifyColumn('payload', text());
            $table->modifyColumn('sandbox_result', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('bnpl_checkout_sessions', function (Blueprint $table) {
            $table->modifyColumn('customer', json()->nullable()->default(''));
            $table->modifyColumn('items', json()->nullable()->default(''));
            $table->modifyColumn('payload', json());
            $table->modifyColumn('sandbox_result', json()->nullable()->default(''));
        });
    }
};