<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'user_type')
            || ! Schema::hasColumn('users', 'transfer_status')
        ) {
            return;
        }

        $settings = collect();
        if (Schema::hasTable('settings')) {
            $settings = DB::table('settings')
                ->whereIn('name', [
                    'transfer_default_buyer',
                    'transfer_default_merchant',
                ])
                ->pluck('val', 'name');
        }

        foreach ([
            'buyer' => 'transfer_default_buyer',
            'merchant' => 'transfer_default_merchant',
        ] as $userType => $settingKey) {
            $enabled = $settings->has($settingKey)
                ? $this->isEnabled($settings->get($settingKey))
                : true;

            $updates = ['transfer_status' => $enabled];
            if (Schema::hasColumn('users', 'updated_at')) {
                $updates['updated_at'] = now();
            }

            DB::table('users')
                ->where('user_type', $userType)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // Data-preserving migration: previous per-user values cannot be
        // reconstructed safely after the role settings have been applied.
    }

    private function isEnabled(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), [
            '1', 'true', 'yes', 'on', 'enabled', 'active',
        ], true);
    }
};
