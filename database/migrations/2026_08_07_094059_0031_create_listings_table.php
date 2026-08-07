<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('listings')) {
            DB::statement('CREATE TABLE `listings` (
  `id` bigint UNSIGNED NOT NULL,
  `seller_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `subcategory_id` int DEFAULT NULL,
  `brand_id` int DEFAULT NULL,
  `type` enum(\'physical\',\'digital\') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT \'digital\',
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `views` int DEFAULT \'0\',
  `discount_type` enum(\'percentage\',\'amount\',\'none\') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT \'none\',
  `discount_value` decimal(10,2) DEFAULT \'0.00\',
  `quantity` int NOT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_speed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT \'draft\',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delivery_speed_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT \'day, min, hrs, sec etc\',
  `is_flash` tinyint DEFAULT \'0\',
  `sold_count` int NOT NULL DEFAULT \'0\',
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provider_id` int DEFAULT NULL,
  `product_url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provider_product_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT \'0\',
  `is_approved` tinyint NOT NULL DEFAULT \'1\',
  `is_trending` tinyint NOT NULL DEFAULT \'0\',
  `avg_rating` float(3,2) NOT NULL DEFAULT \'0.00\',
  `custom_fields` json DEFAULT NULL,
  `has_attributes` tinyint(1) NOT NULL DEFAULT \'0\',
  `shipping_charge` decimal(5,2) DEFAULT NULL,
  `shipping_charge_type` enum(\'fixed\',\'percentage\') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        } else {
            Schema::table('listings', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};