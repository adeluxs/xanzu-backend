<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('phone_otps')) {
            DB::statement('CREATE TABLE `phone_otps` (
  `id` bigint UNSIGNED NOT NULL,
  `dial_code` varchar(5) DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT \'without dial code\',
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) DEFAULT \'0\',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE `phone_otps` ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone` (`phone`)');
            DB::statement('ALTER TABLE `phone_otps` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT');

        } else {
            Schema::table('phone_otps', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_otps');
    }
};