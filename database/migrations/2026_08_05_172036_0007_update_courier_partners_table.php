<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_partners', function (Blueprint $table) {
            $table->modifyColumn('admin_note', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('courier_partners', function (Blueprint $table) {
            $table->modifyColumn('admin_note', text());
        });
    }
};