<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->modifyColumn('reserved_method', text()->nullable()->default(''));
            $table->modifyColumn('url', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->modifyColumn('reserved_method', text());
            $table->modifyColumn('url', text());
        });
    }
};