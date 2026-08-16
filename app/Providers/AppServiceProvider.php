<?php

namespace App\Providers;

use App\Facades\Notification\Notify;
use App\Facades\Txn\Txn;
use App\Models\Setting;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Remotelywork\Installer\Repository\App;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application modules.
     *
     * @return void
     */
    public function register()
    {
        Paginator::defaultView('frontend::include.__pagination');
    }

    /**
     * Bootstrap any application modules.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function boot()
    {

        $this->app->bind('notify', function () {
            return new Notify;
        });

        $this->app->bind('txn', function () {
            return new Txn;
        });

        // Composer package discovery and initial Artisan migrations boot the
        // application before a fresh database necessarily has this table.
        if (Setting::tableExists()) {
            $timezone = setting('site_timezone', 'global');

            config()->set([
                'app.timezone' => $timezone,
                // 'app.debug' => setting('debug_mode', 'permission'),
                // 'debugbar.enabled' => setting('debug_mode', 'permission'),
                'session.lifetime' => setting('session_lifetime', 'system'),
                'session.same_site' => env('APP_DEMO') ? 'none' : 'lax',
            ]);

            date_default_timezone_set($timezone);
        }

        Blade::directive('removeimg', function ($expression) {
            [$isHidden, $img_field] = explode(',', $expression);
            $isHidden = trim($isHidden);
            $img_field = trim($img_field);

            return "<?php \$isHidden = $isHidden; \$img_field = '$img_field'; ?>
            <div data-des=\"<?php echo \$img_field; ?>\" <?php if(!\$isHidden) echo 'hidden'; ?> class=\"close remove-img <?php echo \$img_field; ?>\"><i data-lucide=\"x\"></i></div>";
        });

        // Set string length to 255
        Schema::defaultStringLength(255);

        // Optional production-safe slow-query diagnostics. Enable with
        // PERFORMANCE_LOG_SLOW_QUERIES=true; it does not execute extra queries.
        if ((bool) env('PERFORMANCE_LOG_SLOW_QUERIES', false)) {
            DB::listen(function ($query): void {
                $threshold = max(100, (int) env('PERFORMANCE_SLOW_QUERY_MS', 500));
                if ($query->time >= $threshold) {
                    Log::warning('Slow database query', [
                        'time_ms' => $query->time,
                        'sql' => $query->sql,
                        'connection' => $query->connectionName,
                        'path' => request()?->path(),
                    ]);
                }
            });
        }


        URL::forceScheme('https');

        $this->configureAssetUrl();

    }

    public function configureAssetUrl()
    {
        $assetUrl = url('assets');
        $this->app->singleton('url', function ($app) use ($assetUrl) {
            $routes = $app['router']->getRoutes();
            $request = $app['request'];
            $url = new UrlGenerator($routes, $request, $assetUrl);

            return $url;
        });
    }
}
