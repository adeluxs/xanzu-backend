<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gateways')) {
            return;
        }

        $gateway = DB::table('gateways')->where('gateway_code', 'rayplusmoney')->first();
        if (! $gateway) {
            return;
        }

        if (Schema::hasColumn('gateways', 'is_withdraw')) {
            $current = trim((string) ($gateway->is_withdraw ?? ''));
            if (in_array($current, ['', '0', '1'], true)) {
                DB::table('gateways')->where('id', $gateway->id)->update([
                    'is_withdraw' => 'network,customer',
                    'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::hasTable('withdraw_methods')) {
            return;
        }

        $methods = DB::table('withdraw_methods')
            ->where('gateway_id', $gateway->id)
            ->where('type', 'auto')
            ->get(['id', 'fields']);

        foreach ($methods as $method) {
            $fields = json_decode((string) $method->fields, true);
            $needsRepair = ! is_array($fields) || count($fields) === 0;

            if (is_array($fields) && count($fields) === 1) {
                $only = reset($fields);
                $fieldName = is_array($only) ? trim((string) ($only['name'] ?? '')) : '';
                $needsRepair = $fieldName === '1';
            }

            if (! $needsRepair) {
                continue;
            }

            DB::table('withdraw_methods')->where('id', $method->id)->update([
                'fields' => json_encode([
                    ['name' => 'network', 'type' => 'text', 'validation' => 'required|integer|min:1'],
                    ['name' => 'customer', 'type' => 'text', 'validation' => 'required|string|max:40'],
                ]),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep configured withdrawal credentials intact.
    }
};
