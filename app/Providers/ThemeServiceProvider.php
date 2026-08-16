<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
        // site_theme() safely returns the bundled default theme until the
        // themes table exists, allowing Composer and migrations to boot.
        $theme = site_theme();
        $views = __DIR__.'/../../resources/views/frontend/'.$theme;
        $this->loadViewsFrom($views, 'frontend');

        $request = $this->app['request'];
        if ($theme === 'accxone' && ($request->is('seller/*') || $request->is('user/*'))) {
            Paginator::defaultView('frontend.default.pagination.pagination');
        } else {
            Paginator::defaultView('frontend::pagination.pagination');
        }
    }
}
