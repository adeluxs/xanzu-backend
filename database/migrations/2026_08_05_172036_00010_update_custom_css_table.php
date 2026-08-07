<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_css', function (Blueprint $table) {
            $table->modifyColumn('css', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('custom_css', function (Blueprint $table) {
            $table->modifyColumn('css', text());
        });
    }
};