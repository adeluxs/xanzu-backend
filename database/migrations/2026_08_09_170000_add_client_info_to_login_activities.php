<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Agent\Agent;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('login_activities')) {
            return;
        }

        $addBrowser = ! Schema::hasColumn('login_activities', 'browser');
        $addPlatform = ! Schema::hasColumn('login_activities', 'platform');

        if ($addBrowser || $addPlatform) {
            Schema::table('login_activities', function (Blueprint $table) use ($addBrowser, $addPlatform) {
                if ($addBrowser) {
                    $table->string('browser', 100)->nullable()->after('agent');
                }
                if ($addPlatform) {
                    $table->string('platform', 100)->nullable()->after('browser');
                }
            });
        }

        // Backfill by distinct user-agent rather than one parser + UPDATE per row.
        // This keeps the migration bounded even when login history is large.
        DB::table('login_activities')
            ->select('agent')
            ->whereNotNull('agent')
            ->where('agent', '!=', '')
            ->distinct()
            ->orderBy('agent')
            ->chunk(250, function ($rows) {
                foreach ($rows as $row) {
                    $agent = new Agent;
                    $agent->setUserAgent($row->agent);

                    $browser = trim((string) $agent->browser());
                    $platform = trim((string) $agent->platform());

                    DB::table('login_activities')
                        ->where('agent', $row->agent)
                        ->where(function ($query) {
                            $query->whereNull('browser')->orWhereNull('platform');
                        })
                        ->update([
                            'browser' => $browser !== '' ? mb_substr($browser, 0, 100) : null,
                            'platform' => $platform !== '' ? mb_substr($platform, 0, 100) : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('login_activities')) {
            return;
        }

        $columns = array_values(array_filter(['browser', 'platform'], fn ($column) => Schema::hasColumn('login_activities', $column)));
        if ($columns !== []) {
            Schema::table('login_activities', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
