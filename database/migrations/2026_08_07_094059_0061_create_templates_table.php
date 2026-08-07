<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('templates')) {
            DB::statement('CREATE TABLE `templates` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `for` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'User\',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification_status` tinyint(1) NOT NULL DEFAULT \'1\',
  `email_status` tinyint(1) NOT NULL DEFAULT \'1\',
  `sms_status` tinyint(1) NOT NULL DEFAULT \'1\',
  `sms_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notification_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `short_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `banner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salutation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `button_level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `footer_status` tinyint(1) NOT NULL DEFAULT \'1\',
  `footer_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        } else {
            Schema::table('templates', function (Blueprint $table) {
                // Table already exists, add missing columns if needed
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};