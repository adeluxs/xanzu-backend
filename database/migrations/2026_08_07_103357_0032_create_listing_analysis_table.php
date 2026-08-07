<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('listing_analysis')) {
            DB::statement('CREATE TABLE `listing_analysis` (
  `id` int NOT NULL,
  `listing_id` int NOT NULL,
  `event_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
            DB::statement('ALTER TABLE `listing_analysis` ADD PRIMARY KEY (`id`)');
            DB::statement('ALTER TABLE `listing_analysis` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1108');

        } else {
            Schema::table('listing_analysis', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_analysis');
    }
};