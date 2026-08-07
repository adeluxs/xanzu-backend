<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('listing_galleries')) {
            DB::statement('CREATE TABLE `listing_galleries` (
  `id` int NOT NULL,
  `listing_id` int DEFAULT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
            DB::statement('ALTER TABLE `listing_galleries` ADD PRIMARY KEY (`id`)');
            DB::statement('ALTER TABLE `listing_galleries` MODIFY `id` int NOT NULL AUTO_INCREMENT');

        } else {
            Schema::table('listing_galleries', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_galleries');
    }
};