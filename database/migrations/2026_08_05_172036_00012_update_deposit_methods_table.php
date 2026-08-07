<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_methods', function (Blueprint $table) {
            $table->modifyColumn('field_options', text()->nullable()->default(''));
            $table->modifyColumn('payment_details', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('deposit_methods', function (Blueprint $table) {
            $table->modifyColumn('field_options', text());
            $table->modifyColumn('payment_details', text());
        });
    }
};