<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            [
                'key' => 'transfer_global_status',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'transfer_default_buyer',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'transfer_default_merchant',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'transfer_require_kyc',
                'value' => '0',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'transfer_global_status',
            'transfer_default_buyer',
            'transfer_default_merchant',
            'transfer_require_kyc',
        ])->delete();
    }
};
