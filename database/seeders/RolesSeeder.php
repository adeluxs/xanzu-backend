<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => 1, 'name' => 'Super-Admin', 'guard_name' => 'admin', 'created_at' => '2024-04-28 22:59:41', 'updated_at' => '2024-04-28 22:59:41'],
            ['id' => 3, 'name' => 'admin1@xanzu.com', 'guard_name' => 'admin', 'created_at' => '2025-04-05 10:32:30', 'updated_at' => '2025-04-05 10:32:30'],
        ];

        foreach ($records as $record) {
            DB::table('roles')->updateOrInsert(
                ['id' => $record['id']],
                $record
            );
        }
    }
}
