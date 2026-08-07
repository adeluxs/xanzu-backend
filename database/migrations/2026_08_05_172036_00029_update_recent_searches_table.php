<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recent_searches', function (Blueprint $table) {
            $table->modifyColumn('created_at', timestamp()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('recent_searches', function (Blueprint $table) {
            $table->modifyColumn('created_at', timestamp()->default(''));
        });
    }
};