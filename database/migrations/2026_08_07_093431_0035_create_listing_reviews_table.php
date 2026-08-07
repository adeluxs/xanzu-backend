<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('listing_reviews')) {
            DB::statement('CREATE TABLE `listing_reviews` (
  `id` bigint UNSIGNED NOT NULL,
  `listing_id` bigint UNSIGNED NOT NULL,
  `seller_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `order_item_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `rating` tinyint UNSIGNED NOT NULL COMMENT \'1-5 star rating\',
  `review` text,
  `attachments` json DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT \'pending\',
  `flag_reason` varchar(255) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `buyer_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        } else {
            Schema::table('listing_reviews', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_reviews');
    }
};