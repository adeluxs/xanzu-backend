<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->modifyColumn('supported_currencies', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->modifyColumn('supported_currencies', text());
        });
    }
};