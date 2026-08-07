<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bnpl_installments')) {
            DB::statement('CREATE TABLE `bnpl_installments` (
  `id` bigint UNSIGNED NOT NULL,
  `bnpl_item_loan_id` bigint UNSIGNED NOT NULL,
  `installment_no` int UNSIGNED NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
  `interest_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
  `late_fee_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
  `total_due_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
  `due_at` datetime NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `status` enum(\'pending\',\'paid\',\'partial\',\'overdue\',\'cancelled\',\'processing\') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'pending\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE `bnpl_installments` ADD PRIMARY KEY (`id`),
ADD KEY `bnpl_installments_loan_id_index` (`bnpl_item_loan_id`),
ADD KEY `bnpl_installments_status_index` (`status`)');
            DB::statement('ALTER TABLE `bnpl_installments` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT');

        } else {
            Schema::table('bnpl_installments', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bnpl_installments');
    }
};