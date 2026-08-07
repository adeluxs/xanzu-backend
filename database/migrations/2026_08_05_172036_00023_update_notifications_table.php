<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->modifyColumn('notice', text()->nullable()->default(''));
            $table->modifyColumn('action_url', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->modifyColumn('notice', text());
            $table->modifyColumn('action_url', text());
        });
    }
};