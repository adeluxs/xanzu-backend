<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            DB::statement('CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `order_number` bigint NOT NULL,
  `buyer_id` bigint UNSIGNED NOT NULL,
  `coupon_id` bigint UNSIGNED DEFAULT NULL,
  `subtotal` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `discount_amount` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `shipping_charge_amount` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `shipping_charge_type` enum(\'fixed\',\'percentage\') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT \'fixed\',
  `final_shipping_charge` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `total_price` decimal(16,2) NOT NULL DEFAULT \'0.00\',
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT \'pending\',
  `payment_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT \'pending\',
  `gateway_id` bigint UNSIGNED DEFAULT NULL,
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estimated_delivery_from` datetime DEFAULT NULL,
  `estimated_delivery_to` datetime DEFAULT NULL,
  `courier_partner_id` bigint UNSIGNED DEFAULT NULL,
  `tracking_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_bnpl` tinyint(1) NOT NULL DEFAULT \'0\',
  `bnpl_upfront_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE `orders` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `order_number_unique` (`order_number`),
ADD KEY `buyer_index` (`buyer_id`),
ADD KEY `orders_status_index` (`status`),
ADD KEY `orders_payment_status_index` (`payment_status`),
ADD KEY `orders_order_date_index` (`order_date`),
ADD KEY `orders_is_bnpl_index` (`is_bnpl`),
ADD KEY `orders_courier_partner_id_index` (`courier_partner_id`)');
            DB::statement('ALTER TABLE `orders` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT');
            DB::statement('ALTER TABLE `orders` ADD CONSTRAINT `orders_courier_partner_id_foreign` FOREIGN KEY (`courier_partner_id`) REFERENCES `courier_partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');

        } else {
            Schema::table('orders', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};