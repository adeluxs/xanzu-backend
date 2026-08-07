<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('messages')) {
            DB::statement('CREATE TABLE `messages` (
  `id` bigint UNSIGNED NOT NULL,
  `model` enum(\'user\',\'admin\') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'user\',
  `user_id` bigint UNSIGNED NOT NULL,
  `parent_id` int DEFAULT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE `messages` ADD PRIMARY KEY (`id`),
ADD KEY `messages_user_id_index` (`user_id`),
ADD KEY `messages_ticket_id_index` (`ticket_id`)');
            DB::statement('ALTER TABLE `messages` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT');

        } else {
            Schema::table('messages', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};