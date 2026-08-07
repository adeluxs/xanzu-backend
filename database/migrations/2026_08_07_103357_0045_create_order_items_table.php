<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_items')) {
            DB::statement('CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `listing_id` bigint UNSIGNED NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seller_id` bigint UNSIGNED DEFAULT NULL,
  `provider_id` int DEFAULT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `plan_id` bigint UNSIGNED DEFAULT NULL,
  `is_topup` tinyint(1) NOT NULL DEFAULT \'0\',
  `quantity` int UNSIGNED NOT NULL DEFAULT \'1\',
  `org_unit_price` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `unit_price` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `total_price` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `selected_attributes` json DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT \'pending\',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE `order_items` ADD PRIMARY KEY (`id`),
ADD KEY `order_items_order_id_index` (`order_id`),
ADD KEY `order_items_listing_id_index` (`listing_id`),
ADD KEY `order_items_seller_id_index` (`seller_id`),
ADD KEY `order_items_status_index` (`status`)');
            DB::statement('ALTER TABLE `order_items` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT');

        } else {
            Schema::table('order_items', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};