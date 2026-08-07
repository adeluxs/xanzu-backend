<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chats')) {
            DB::statement('CREATE TABLE `chats` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `seen` tinyint(1) DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
            DB::statement('ALTER TABLE `chats` ADD PRIMARY KEY (`id`),
ADD KEY `chats_sender_id_index` (`sender_id`),
ADD KEY `chats_receiver_id_index` (`receiver_id`),
ADD KEY `chats_seen_index` (`seen`)');
            DB::statement('ALTER TABLE `chats` MODIFY `id` int NOT NULL AUTO_INCREMENT');

        } else {
            Schema::table('chats', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};