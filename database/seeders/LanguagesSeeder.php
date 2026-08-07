<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguagesSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => 1, 'flag' => null, 'name' => 'English', 'locale' => 'en', 'is_rtl' => 0, 'is_default' => 1, 'status' => 1, 'created_at' => null, 'updated_at' => '2025-03-25 06:37:31'],
        ];

        foreach ($records as $record) {
            DB::table('languages')->updateOrInsert(
                ['id' => $record['id']],
                $record
            );
        }
    }
}
