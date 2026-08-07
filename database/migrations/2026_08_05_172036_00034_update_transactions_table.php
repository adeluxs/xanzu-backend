<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->modifyColumn('manual_field_data', text()->nullable()->default(''));
            $table->modifyColumn('approval_cause', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->modifyColumn('manual_field_data', text());
            $table->modifyColumn('approval_cause', text());
        });
    }
};