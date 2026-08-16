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
        if (! Schema::hasTable('listings')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            if (!Schema::hasColumn('listings', 'shipping_charge')) {
                $table->decimal('shipping_charge', 28, 2)->nullable()->after('discount_type');
            }

            if (!Schema::hasColumn('listings', 'shipping_charge_type')) {
                $table->string('shipping_charge_type', 50)->nullable()->after('shipping_charge');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('listings')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'shipping_charge_type')) {
                $table->dropColumn('shipping_charge_type');
            }

            if (Schema::hasColumn('listings', 'shipping_charge')) {
                $table->dropColumn('shipping_charge');
            }
        });
    }
};
