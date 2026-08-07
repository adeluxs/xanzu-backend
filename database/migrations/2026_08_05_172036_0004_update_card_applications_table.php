<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_applications', function (Blueprint $table) {
            $table->modifyColumn('admin_note', text()->nullable()->default(''));
            $table->modifyColumn('created_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('updated_at', timestamp()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('card_applications', function (Blueprint $table) {
            $table->modifyColumn('admin_note', text());
            $table->modifyColumn('created_at', timestamp()->nullable()->default(''));
            $table->modifyColumn('updated_at', timestamp()->nullable()->default(''));
        });
    }
};