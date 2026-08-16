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
        // Fresh installations create this table in a later imported-schema
        // migration, which already includes these columns.
        if (Schema::hasTable('providers')) {
            Schema::table('providers', function (Blueprint $table) {
                if (! Schema::hasColumn('providers', 'platform')) {
                    $table->string('platform', 255)->default('wordpress-woocommerce');
                }
                if (! Schema::hasColumn('providers', 'platform_host')) {
                    $table->string('platform_host', 255)->nullable();
                }
                if (! Schema::hasColumn('providers', 'api_key')) {
                    $table->string('api_key', 255)->nullable();
                }
                if (! Schema::hasColumn('providers', 'api_secret')) {
                    $table->string('api_secret', 255)->nullable();
                }
            });
        }

        // Add BNPL fields to users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'api_key')) {
                    $table->string('api_key', 255)->nullable();
                }
                if (! Schema::hasColumn('users', 'signature')) {
                    $table->string('signature', 255)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('providers')) {
            Schema::table('providers', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['platform', 'platform_host', 'api_key', 'api_secret'],
                    fn ($column) => Schema::hasColumn('providers', $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['api_key', 'signature'],
                    fn ($column) => Schema::hasColumn('users', $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
