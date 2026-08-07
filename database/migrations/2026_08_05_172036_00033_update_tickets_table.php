<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->modifyColumn('message', text()->nullable()->default(''));
            $table->modifyColumn('attachments', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->modifyColumn('message', text());
            $table->modifyColumn('attachments', json()->nullable()->default(''));
        });
    }
};