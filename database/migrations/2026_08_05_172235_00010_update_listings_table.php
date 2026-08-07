<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->modifyColumn('created_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('updated_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('custom_fields', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->modifyColumn('created_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('updated_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('custom_fields', json()->nullable()->default(''));
        });
    }
};