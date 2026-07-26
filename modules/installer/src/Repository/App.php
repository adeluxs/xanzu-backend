<?php

namespace Remotelywork\Installer\Repository;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class App
{
    protected static $token = 'E9dgn4KPtrRxUwdZ4n03tsc3qesqzBiN';

    protected static $scriptId = 59577555;

    protected static $appId = 58566396;

    protected static $webCacheKey = 'web_license_validated';

    protected static $appCacheKey = 'app_license_validated';

    protected static $validatedTtl = 86400;

    protected static $installedFile = 'installed';

    public static function dbConnectionCheck(): bool
    {
        $ok = false;

        try {

            DB::getPdo();
            DB::connection()->getDatabaseName();

            $ok = true;

            if (! file_exists(storage_path(self::$installedFile))) {
                $ok = false;
            }

            return $ok;

        } catch (\Throwable $th) {
            $ok = false;
        }

        return $ok;
    }

    public static function initApp()
    {
        return true;
    }

    public static function validateLicense($code = null, string $type = 'web')
    {

        return true;

        if (env('APP_DEMO')) {
            return true;
        }

        $code = $code ?? config('app.license_key');
        if (empty($code)) {
            return false;
        }

        $expectedItemId = ($type === 'web') ? self::$scriptId : self::$appId;

        if (self::isLicenseCached($code, $type, $expectedItemId)) {
            return true;
        }

        $cacheKey = self::getCacheKey($code, $type, $expectedItemId);

        $response = Http::withToken(self::$token)
            ->withOptions(['verify' => false])
            ->get('https://api.envato.com/v3/market/author/sale', [
                'code' => $code,
            ]);

        if (! $response->successful()) {
            Cache::forget($cacheKey);

            return false;
        }

        $data = $response->json();

        if (($data['item']['id'] ?? null) !== $expectedItemId) {
            return false;
        }

        Cache::put($cacheKey, true, self::$validatedTtl);

        return true;
    }

    public static function isLicenseCached(string $code, string $type = 'web', ?int $itemId = null): bool
    {
        $itemId ??= ($type === 'web') ? self::$scriptId : self::$appId;

        $cacheKey = self::getCacheKey($code, $type, $itemId);

        return Cache::has($cacheKey);
    }

    protected static function getCacheKey(string $code, string $type, int $itemId): string
    {
        return ($type === 'web' ? self::$webCacheKey : self::$appCacheKey)
            .'_'.md5($code.$itemId);
    }
}
