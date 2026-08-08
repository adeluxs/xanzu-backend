<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            foreach ([
                ['name' => 'transfer_global_status', 'val' => '1', 'type' => 'boolean'],
                ['name' => 'transfer_default_buyer', 'val' => '1', 'type' => 'boolean'],
                ['name' => 'transfer_default_merchant', 'val' => '1', 'type' => 'boolean'],
                ['name' => 'transfer_require_kyc', 'val' => '0', 'type' => 'boolean'],
            ] as $setting) {
                DB::table('settings')->updateOrInsert(
                    ['name' => $setting['name']],
                    array_merge($setting, ['updated_at' => now(), 'created_at' => now()])
                );
            }
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'transfer_status')) {
                    $table->boolean('transfer_status')->default(1);
                }
                if (! Schema::hasColumn('users', 'transfer_kyc_verified')) {
                    $table->boolean('transfer_kyc_verified')->default(0);
                }
            });
        }

        if (Schema::hasTable('gateways')) {
            $exists = DB::table('gateways')->where('gateway_code', 'rayplusmoney')->exists();
            if (! $exists) {
                $gateway = [
                    'name' => 'RayPlusMoney',
                    'gateway_code' => 'rayplusmoney',
                    'credentials' => json_encode([
                        'base_url' => 'https://app.rayplusmoney.com/pay/v01',
                        'api_key' => '',
                        'api_token' => '',
                        'payout_network' => '',
                    ]),
                    'status' => 0,
                ];

                // The script has existed across several schema generations.
                // Populate optional columns only when they are present so the
                // repair migration works on both upgraded and fresh installs.
                foreach ([
                    'logo' => 'global/gateway/rayplusmoney.png',
                    'type' => 'auto',
                    'supported_currencies' => json_encode(['XOF']),
                    'is_withdraw' => 'network,customer',
                    'created_at' => now(),
                    'updated_at' => now(),
                ] as $column => $value) {
                    if (Schema::hasColumn('gateways', $column)) {
                        $gateway[$column] = $value;
                    }
                }

                DB::table('gateways')->insert($gateway);
            }
        }
    }

    public function down(): void
    {
        // Data-preserving migration: do not remove settings or gateway records
        // that may have been configured after installation.
    }
};
