<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bnpl_item_loans')) {
            DB::statement('CREATE TABLE `bnpl_item_loans` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `order_item_id` bigint UNSIGNED NOT NULL,
  `credit_limit_split_id` bigint UNSIGNED NOT NULL,
  `total_item_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\' COMMENT \'item total\',
  `initial_paid_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\' COMMENT \'paid instantly\',
  `final_amount_to_pay` decimal(15,2) NOT NULL DEFAULT \'0.00\' COMMENT \'moved to credit\',
  `remaining_due_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\' COMMENT \'remaining due\',
  `total_split` int UNSIGNED NOT NULL DEFAULT \'1\',
  `payment_interval_amount` int UNSIGNED NOT NULL DEFAULT \'1\',
  `payment_interval_type` enum(\'day\',\'week\',\'month\') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'month\',
  `interest_rate_amount` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `interest_rate_type` enum(\'percentage\',\'fixed\') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'fixed\',
  `delay_fine_amount` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `delay_fine_type` enum(\'percentage\',\'fixed\') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'fixed\',
  `status` enum(\'pending\',\'partially_paid\',\'paid\',\'overdue\',\'cancelled\') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'pending\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        } else {
            Schema::table('bnpl_item_loans', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bnpl_item_loans');
    }
};