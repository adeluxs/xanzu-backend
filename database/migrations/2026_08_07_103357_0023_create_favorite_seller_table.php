<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('favorite_seller')) {
            DB::statement('CREATE TABLE `favorite_seller` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE `favorite_seller` ADD PRIMARY KEY (`id`)');

        } else {
            Schema::table('favorite_seller', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_seller');
    }
};