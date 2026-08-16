<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'public_key')) {
                $table->string('public_key', 191)->nullable();
            }

            if (! Schema::hasColumn('users', 'secret_key')) {
                $table->string('secret_key', 191)->nullable();
            }

            if (! Schema::hasColumn('users', 'webhook_secret')) {
                $table->string('webhook_secret', 191)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('users', 'public_key')) {
                $dropColumns[] = 'public_key';
            }

            if (Schema::hasColumn('users', 'secret_key')) {
                $dropColumns[] = 'secret_key';
            }

            if (Schema::hasColumn('users', 'webhook_secret')) {
                $dropColumns[] = 'webhook_secret';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
