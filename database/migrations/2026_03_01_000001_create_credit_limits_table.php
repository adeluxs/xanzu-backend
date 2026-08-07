<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_limits')) {
            DB::statement('CREATE TABLE `credit_limits` (
  `id` bigint UNSIGNED NOT NULL,
  `level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum_transactions` int NOT NULL,
  `is_kyc` tinyint(1) NOT NULL DEFAULT \'0\',
  `credit_amount` float(10,2) NOT NULL DEFAULT \'0.00\',
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        }

        $hasPrimaryKey = DB::select("SHOW KEYS FROM `credit_limits` WHERE Key_name = 'PRIMARY'");
        if (empty($hasPrimaryKey)) {
            DB::statement('ALTER TABLE `credit_limits` ADD PRIMARY KEY (`id`)');
        }

        DB::statement('ALTER TABLE `credit_limits` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_limits');
    }
};
