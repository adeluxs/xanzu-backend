<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $rows = [
            [
                'name' => 'transfer_global_status',
                'val' => '1',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'transfer_default_buyer',
                'val' => '1',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'transfer_default_merchant',
                'val' => '1',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'transfer_require_kyc',
                'val' => '0',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($rows as $row) {
            DB::table('settings')->updateOrInsert(
                ['name' => $row['name']],
                $row
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('name', [
            'transfer_global_status',
            'transfer_default_buyer',
            'transfer_default_merchant',
            'transfer_require_kyc',
        ])->delete();
    }
};
