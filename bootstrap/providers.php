<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\GatewayServiceProvider;
use App\Providers\PluginServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\SettingServiceProvider;
use App\Providers\ThemeServiceProvider;
use App\Providers\TxnProvider;
use App\Providers\ViewServiceProvider;
// use Fruitcake\LaravelDebugbar\ServiceProvider;




return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    RouteServiceProvider::class,
    ViewServiceProvider::class,
    TxnProvider::class,
    GatewayServiceProvider::class,
    SettingServiceProvider::class,
    PluginServiceProvider::class,
    ThemeServiceProvider::class,
    // ServiceProvider::class,
];
