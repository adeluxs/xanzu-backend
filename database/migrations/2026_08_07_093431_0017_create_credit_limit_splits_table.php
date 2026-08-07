<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_limit_splits')) {
            DB::statement('CREATE TABLE `credit_limit_splits` (
  `id` bigint UNSIGNED NOT NULL,
  `credit_limit_id` bigint UNSIGNED DEFAULT NULL,
  `total_split` int DEFAULT NULL,
  `payment_interval_amount` int NOT NULL,
  `payment_interval_type` enum(\'day\',\'week\',\'month\') NOT NULL,
  `interest_rate_amount` float(5,2) NOT NULL DEFAULT \'0.00\',
  `interest_rate_type` enum(\'percentage\',\'fixed\') NOT NULL,
  `delay_fine_amount` float(10,2) NOT NULL DEFAULT \'0.00\',
  `delay_fine_type` enum(\'percentage\',\'fixed\') NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT \'1\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        } else {
            Schema::table('credit_limit_splits', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_limit_splits');
    }
};