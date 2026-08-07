<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_reviews', function (Blueprint $table) {
            $table->modifyColumn('attachments', text()->nullable()->default(''));
            $table->modifyColumn('reviewed_at', timestamp()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('listing_reviews', function (Blueprint $table) {
            $table->modifyColumn('attachments', json()->nullable()->default(''));
            $table->modifyColumn('reviewed_at', timestamp()->nullable()->default(''));
        });
    }
};