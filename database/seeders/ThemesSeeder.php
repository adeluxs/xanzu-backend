<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemesSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => 14, 'name' => 'default', 'type' => 'site', 'status' => 1, 'created_at' => '2023-07-04 12:47:28', 'updated_at' => '2026-04-12 06:11:14'],
        ];

        foreach ($records as $record) {
            DB::table('themes')->updateOrInsert(
                ['id' => $record['id']],
                $record
            );
        }
    }
}
