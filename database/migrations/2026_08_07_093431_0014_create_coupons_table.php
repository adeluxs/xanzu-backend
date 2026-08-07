<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('coupons')) {
            DB::statement('CREATE TABLE `coupons` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_home` tinyint(1) DEFAULT NULL,
  `discount_type` enum(\'percentage\',\'amount\') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `discount_value` decimal(8,2) NOT NULL,
  `max_use_limit` int NOT NULL,
  `total_used` int NOT NULL DEFAULT \'0\',
  `status` tinyint NOT NULL DEFAULT \'1\',
  `expires_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        } else {
            Schema::table('coupons', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};