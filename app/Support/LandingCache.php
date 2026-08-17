<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class LandingCache
{
    private const VERSION_KEY = 'api.landing.version';

    public static function version(): int
    {
        return max(1, (int) Cache::get(self::VERSION_KEY, 1));
    }

    /**
     * Move all landing API requests to a fresh cache namespace.
     *
     * Laravel's cache store cannot reliably delete keys by prefix on every
     * supported driver, so a versioned namespace is safer and works with file,
     * database, Redis, Memcached, and array stores.
     */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
