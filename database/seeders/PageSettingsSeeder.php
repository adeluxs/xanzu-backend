<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => 3, 'key' => 'username_show', 'value' => 1, 'created_at' => '2023-05-24 11:46:21', 'updated_at' => '2025-03-23 09:16:57'],
            ['id' => 6, 'key' => 'referral_code_show', 'value' => 1, 'created_at' => '2023-05-24 11:46:21', 'updated_at' => '2024-06-11 23:23:59'],
            ['id' => 8, 'key' => 'username_validation', 'value' => 0, 'created_at' => '2024-03-20 02:58:39', 'updated_at' => '2026-02-16 08:55:01'],
            ['id' => 13, 'key' => 'gender_show', 'value' => 1, 'created_at' => '2024-03-20 03:13:18', 'updated_at' => '2024-03-20 03:13:18'],
            ['id' => 14, 'key' => 'gender_validation', 'value' => 1, 'created_at' => '2024-03-20 03:13:18', 'updated_at' => '2024-03-20 03:27:14'],
            ['id' => 25, 'key' => 'first_name_show', 'value' => 1, 'created_at' => '2025-04-07 05:55:36', 'updated_at' => '2025-04-08 11:16:58'],
            ['id' => 26, 'key' => 'first_name_validation', 'value' => 1, 'created_at' => '2025-04-07 05:55:36', 'updated_at' => '2025-04-07 06:07:27'],
            ['id' => 27, 'key' => 'last_name_show', 'value' => 1, 'created_at' => '2025-04-07 05:55:36', 'updated_at' => '2025-04-07 05:55:36'],
            ['id' => 28, 'key' => 'last_name_validation', 'value' => 1, 'created_at' => '2025-04-07 05:55:36', 'updated_at' => '2025-04-07 06:07:27'],
            ['id' => 41, 'key' => 'app_splash_one_image', 'value' => 'global/uploadsglobal/images/RqR5YeQMYzBiKyyClvo3.png', 'created_at' => '2026-02-16 03:58:20', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 42, 'key' => 'app_splash_two_image', 'value' => 'global/uploadsglobal/images/FBNt0y5VTCEoJxnkDvV2.png', 'created_at' => '2026-02-16 03:58:20', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 43, 'key' => 'app_splash_three_image', 'value' => 'global/uploadsglobal/images/RkXSsnMwOhcsQ2OraSN7.png', 'created_at' => '2026-02-16 03:58:20', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 45, 'key' => 'referral_code_validation', 'value' => 0, 'created_at' => '2026-02-16 08:55:01', 'updated_at' => '2026-02-16 08:55:01'],
            ['id' => 46, 'key' => 'app_splash_one_title', 'value' => 'Shop Now, Pay Later', 'created_at' => '2026-02-17 05:27:02', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 47, 'key' => 'app_splash_one_description', 'value' => 'Buy what you need today and split your payments into easy, flexible installments with Xanzo.', 'created_at' => '2026-02-17 05:27:02', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 48, 'key' => 'app_splash_two_title', 'value' => 'Pay Your Way', 'created_at' => '2026-02-17 05:27:02', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 49, 'key' => 'app_splash_two_description', 'value' => 'Choose pay-later plans with clear schedules, no hidden fees, and full control over your spending.', 'created_at' => '2026-02-17 05:27:02', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 50, 'key' => 'app_splash_three_title', 'value' => 'Approved in Minutes', 'created_at' => '2026-02-17 05:27:02', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 51, 'key' => 'app_splash_three_description', 'value' => 'Quick signup, instant approval, and secure payments—start using Xanzo anytime, anywhere.', 'created_at' => '2026-02-17 05:27:02', 'updated_at' => '2026-04-19 11:02:06'],
            ['id' => 54, 'key' => 'merchant_username_show', 'value' => 1, 'created_at' => '2023-05-24 11:46:21', 'updated_at' => '2025-03-23 09:16:57'],
            ['id' => 55, 'key' => 'merchant_referral_code_show', 'value' => 1, 'created_at' => '2023-05-24 11:46:21', 'updated_at' => '2024-06-11 23:23:59'],
            ['id' => 56, 'key' => 'merchant_username_validation', 'value' => 0, 'created_at' => '2024-03-20 02:58:39', 'updated_at' => '2026-02-16 08:55:01'],
            ['id' => 57, 'key' => 'merchant_gender_show', 'value' => 1, 'created_at' => '2024-03-20 03:13:18', 'updated_at' => '2024-03-20 03:13:18'],
            ['id' => 58, 'key' => 'merchant_gender_validation', 'value' => 1, 'created_at' => '2024-03-20 03:13:18', 'updated_at' => '2024-03-20 03:27:14'],
            ['id' => 59, 'key' => 'merchant_first_name_show', 'value' => 1, 'created_at' => '2025-04-07 05:55:36', 'updated_at' => '2025-04-08 11:16:58'],
            ['id' => 60, 'key' => 'merchant_first_name_validation', 'value' => 1, 'created_at' => '2025-04-07 05:55:36', 'updated_at' => '2025-04-07 06:07:27'],
            ['id' => 61, 'key' => 'merchant_last_name_show', 'value' => 1, 'created_at' => '2025-04-07 05:55:36', 'updated_at' => '2025-04-07 05:55:36'],
            ['id' => 62, 'key' => 'merchant_last_name_validation', 'value' => 1, 'created_at' => '2025-04-07 05:55:36', 'updated_at' => '2025-04-07 06:07:27'],
        ];

        foreach ($records as $record) {
            DB::table('page_settings')->updateOrInsert(
                ['id' => $record['id']],
                $record
            );
        }
    }
}
