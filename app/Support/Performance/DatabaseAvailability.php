<?php

namespace App\Support\Performance;

use Remotelywork\Installer\Repository\App as InstallerApp;

/**
 * Installer-aware database availability check cached for the lifetime of one
 * PHP request. Settings and service providers call this frequently, so
 * repeating a connection probe for every setting lookup is unnecessary work.
 */
final class DatabaseAvailability
{
    private static ?bool $available = null;

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

    public static function reset(): void
    {
        self::$available = null;
    }
}
