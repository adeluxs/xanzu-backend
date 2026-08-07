<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->modifyColumn('sms_body', text()->nullable()->default(''));
            $table->modifyColumn('email_body', text()->nullable()->default(''));
            $table->modifyColumn('notification_body', text()->nullable()->default(''));
            $table->modifyColumn('short_codes', text()->nullable()->default(''));
            $table->modifyColumn('salutation', text()->nullable()->default(''));
            $table->modifyColumn('footer_body', text()->nullable()->default(''));
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->modifyColumn('sms_body', text());
            $table->modifyColumn('email_body', text());
            $table->modifyColumn('notification_body', text());
            $table->modifyColumn('short_codes', text());
            $table->modifyColumn('salutation', text());
            $table->modifyColumn('footer_body', text());
        });
    }
};