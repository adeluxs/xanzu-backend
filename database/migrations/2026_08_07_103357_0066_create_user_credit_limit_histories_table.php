<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_credit_limit_histories')) {
            DB::statement('CREATE TABLE `user_credit_limit_histories` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `credit_limit_id` bigint UNSIGNED NOT NULL,
  `for` enum(\'kyc\',\'transaction\') NOT NULL,
  `threshold_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
  `credit_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE `user_credit_limit_histories` ADD PRIMARY KEY (`id`)');
            DB::statement('ALTER TABLE `user_credit_limit_histories` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT');

        } else {
            Schema::table('user_credit_limit_histories', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credit_limit_histories');
    }
};