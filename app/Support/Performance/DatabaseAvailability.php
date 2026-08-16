<?php

namespace App\Support\Performance;

use Illuminate\Support\Facades\Schema;
use Remotelywork\Installer\Repository\App as InstallerApp;
use Throwable;

/**
 * Installer-aware database availability check cached for the lifetime of one
 * PHP request. Settings and service providers call this frequently, so
 * repeating a connection probe for every setting lookup is unnecessary work.
 */
final class DatabaseAvailability
{
    private static ?bool $available = null;

    /** @var array<string, bool> */
    private static array $availableTables = [];

    public static function check(): bool
    {
        if (self::$available !== null) {
            return self::$available;
        }

        try {
            return self::$available = (bool) InstallerApp::dbConnectionCheck();
        } catch (\Throwable) {
            return self::$available = false;
        }
    }

    /**
     * Check both the connection and a table before boot-time model queries.
     *
     * Composer package discovery and Artisan migrations boot the application
     * before a new or partially imported database contains every table.
     */
    public static function tableExists(string $table): bool
    {
        if (array_key_exists($table, self::$availableTables)) {
            return self::$availableTables[$table];
        }

        if (! self::check()) {
            return self::$availableTables[$table] = false;
        }

        try {
            return self::$availableTables[$table] = Schema::hasTable($table);
        } catch (Throwable) {
            return self::$availableTables[$table] = false;
        }
    }

    public static function reset(): void
    {
        self::$available = null;
        self::$availableTables = [];
    }
}
