<?php

namespace App\Providers;

use App\Support\Performance\DatabaseAvailability;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Remotelywork\Installer\Repository\App;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (DatabaseAvailability::check()) {
            $views = __DIR__.'/../../resources/views/frontend/'.site_theme();
            $this->loadViewsFrom($views, 'frontend');

            $request = $this->app['request'];
            if (site_theme() == 'accxone' && ($request->is('seller/*') || $request->is('user/*'))) {
                Paginator::defaultView('frontend.default.pagination.pagination');
            } else {
                Paginator::defaultView('frontend::pagination.pagination');
            }
        }

    }
}
