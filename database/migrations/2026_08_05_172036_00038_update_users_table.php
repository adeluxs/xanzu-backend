<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->modifyColumn('address', text()->nullable()->default(''));
            $table->modifyColumn('close_reason', text()->nullable()->default(''));
            $table->modifyColumn('google2fa_secret', text()->nullable()->default(''));
            $table->modifyColumn('about', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->modifyColumn('address', text());
            $table->modifyColumn('close_reason', text());
            $table->modifyColumn('google2fa_secret', text());
            $table->modifyColumn('about', text());
        });
    }
};