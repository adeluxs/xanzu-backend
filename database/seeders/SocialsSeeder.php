<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SocialsSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => 1, 'icon_name' => 'Facebook', 'class_name' => 'fa-brands fa-facebook', 'url' => 'https://www.facebook.com/', 'position' => 0, 'created_at' => '2022-10-25 13:35:16', 'updated_at' => '2025-12-09 06:11:03'],
            ['id' => 2, 'icon_name' => 'Instagram', 'class_name' => 'fa-brands fa-instagram', 'url' => 'https://www.instagram.com', 'position' => 1, 'created_at' => '2022-10-25 13:35:45', 'updated_at' => '2025-12-09 06:11:14'],
            ['id' => 3, 'icon_name' => 'linkedin', 'class_name' => 'fa-brands fa-youtube', 'url' => 'https://www.linkedin.com/', 'position' => 2, 'created_at' => '2022-11-16 17:53:02', 'updated_at' => '2026-04-25 10:22:07'],
        ];

        foreach ($records as $record) {
            DB::table('socials')->updateOrInsert(
                ['id' => $record['id']],
                $record
            );
        }
    }
}
