<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('listing_attributes')) {
            DB::statement('CREATE TABLE `listing_attributes` (
  `id` bigint UNSIGNED NOT NULL,
  `listing_id` bigint UNSIGNED NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT \'e.g. color, storage, size\',
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT \'e.g. Red, 128GB, XL\',
  `price` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `discount_type` enum(\'percentage\',\'amount\') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_amount` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `final_price` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `qty` int UNSIGNED NOT NULL DEFAULT \'0\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE `listing_attributes` ADD PRIMARY KEY (`id`)');
            DB::statement('ALTER TABLE `listing_attributes` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT');

        } else {
            Schema::table('listing_attributes', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_attributes');
    }
};