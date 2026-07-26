<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add BNPL fields to providers table
        Schema::table('providers', function (Blueprint $table) {
            if (!Schema::hasColumn('providers', 'platform')) {
                $table->string('platform', 255)->default('wordpress-woocommerce')->after('user_id');
            }
            if (!Schema::hasColumn('providers', 'platform_host')) {
                $table->string('platform_host', 255)->nullable()->after('platform');
            }
            if (!Schema::hasColumn('providers', 'api_key')) {
                $table->string('api_key', 255)->nullable()->after('platform_host');
            }
            if (!Schema::hasColumn('providers', 'api_secret')) {
                $table->string('api_secret', 255)->nullable()->after('api_key');
            }
        });

        // Add BNPL fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'api_key')) {
                $table->string('api_key', 255)->nullable()->after('default_split');
            }
            if (!Schema::hasColumn('users', 'signature')) {
                $table->string('signature', 255)->nullable()->after('api_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['platform', 'platform_host', 'api_key', 'api_secret']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'signature']);
        });
    }
};