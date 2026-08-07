<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationsSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => 1, 'page_id' => 314, 'name' => 'About', 'url' => 'about-us', 'type' => '[\"header\"]', 'footer_position' => null, 'header_position' => 1, 'status' => 1, 'created_at' => '2026-04-25 08:33:27', 'updated_at' => '2026-04-25 08:33:27', 'translate' => null],
            ['id' => 2, 'page_id' => 318, 'name' => 'Terms and Conditions', 'url' => 'terms-and-conditions', 'type' => '[\"header\"]', 'footer_position' => null, 'header_position' => 4, 'status' => 1, 'created_at' => '2026-04-25 08:33:42', 'updated_at' => '2026-04-25 08:35:01', 'translate' => null],
            ['id' => 4, 'page_id' => null, 'name' => 'Home', 'url' => '/', 'type' => '[\"footer-widget-1\"]', 'footer_position' => null, 'header_position' => null, 'status' => 1, 'created_at' => '2026-04-25 08:35:37', 'updated_at' => '2026-04-25 08:35:37', 'translate' => null],
            ['id' => 5, 'page_id' => 314, 'name' => 'About', 'url' => 'about-us', 'type' => '[\"footer-widget-1\"]', 'footer_position' => null, 'header_position' => null, 'status' => 1, 'created_at' => '2026-04-25 08:35:48', 'updated_at' => '2026-04-25 08:35:48', 'translate' => null],
            ['id' => 6, 'page_id' => 317, 'name' => 'Privacy Policy', 'url' => 'privacy-policy', 'type' => '[\"footer-widget-1\"]', 'footer_position' => null, 'header_position' => null, 'status' => 1, 'created_at' => '2026-04-25 08:35:59', 'updated_at' => '2026-04-25 08:35:59', 'translate' => null],
            ['id' => 7, 'page_id' => 318, 'name' => 'Terms & Conditions', 'url' => 'terms-and-conditions', 'type' => '[\"footer-widget-1\"]', 'footer_position' => null, 'header_position' => null, 'status' => 1, 'created_at' => '2026-04-25 08:36:13', 'updated_at' => '2026-04-25 08:36:13', 'translate' => null],
        ];

        foreach ($records as $record) {
            DB::table('navigations')->updateOrInsert(
                ['id' => $record['id']],
                $record
            );
        }
    }
}
