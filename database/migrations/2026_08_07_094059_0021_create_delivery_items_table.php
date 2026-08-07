<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_items')) {
            DB::statement('CREATE TABLE `delivery_items` (
  `id` int NOT NULL,
  `listing_id` int NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `order_id` int DEFAULT NULL,
  `is_used` tinyint NOT NULL DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        } else {
            Schema::table('delivery_items', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};