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
        Schema::table('listings', function (Blueprint $table) {
            if (!Schema::hasColumn('listings', 'product_url')) {
                $table->string('product_url', 2048)->nullable()->after('provider_id');
            }

            if (!Schema::hasColumn('listings', 'provider_product_id')) {
                $table->string('provider_product_id', 191)->nullable()->after('product_url');
                $table->index(['provider_id', 'provider_product_id'], 'listings_provider_provider_product_id_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'provider_product_id')) {
                $table->dropIndex('listings_provider_provider_product_id_idx');
                $table->dropColumn('provider_product_id');
            }

            if (Schema::hasColumn('listings', 'product_url')) {
                $table->dropColumn('product_url');
            }
        });
    }
};
