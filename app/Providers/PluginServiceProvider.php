<?php

namespace App\Providers;

use App\Support\JsonData;
use App\Support\Performance\DatabaseAvailability;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        if (DatabaseAvailability::tableExists('plugins')) {

            // Nexmo sms plugin
            if (plugin_active('Nexmo')) {
                $nexmoCredential = JsonData::decodeArray(plugin_active('Nexmo')->data);
                config()->set([
                    'sms.connections.nexmo.nexmo_from' => $nexmoCredential['from'] ?? null,
                    'sms.connections.nexmo.api_key' => $nexmoCredential['api_key'] ?? null,
                    'sms.connections.nexmo.api_secret' => $nexmoCredential['api_secret'] ?? null,
                ]);
            }

            // Twilio sms plugin
            if (plugin_active('Twilio')) {
                $twilioCredential = JsonData::decodeArray(plugin_active('Twilio')->data);
                config()->set([
                    'sms.connections.twilio.twilio_sid' => $twilioCredential['twilio_sid'] ?? null,
                    'sms.connections.twilio.twilio_auth_token' => $twilioCredential['twilio_auth_token'] ?? null,
                    'sms.connections.twilio.twilio_phone' => $twilioCredential['twilio_phone'] ?? null,
                ]);
            }

            // Pusher Notification plugin
            if (plugin_active('Pusher')) {
                $push_notification = plugin_active('Pusher');
                if ($push_notification->name == 'Pusher') {
                    $pusherCredential = JsonData::decodeArray($push_notification->data);
                    config()->set([
                        'broadcasting.connections.pusher.app_id' => $pusherCredential['pusher_app_id'] ?? null,
                        'broadcasting.connections.pusher.key' => $pusherCredential['pusher_app_key'] ?? null,
                        'broadcasting.connections.pusher.secret' => $pusherCredential['pusher_app_secret'] ?? null,
                        'broadcasting.connections.pusher.options.cluster' => $pusherCredential['pusher_app_cluster'] ?? null,
                    ]);
                }
            }

            // Reloadly Plugin
            if (plugin_active('Reloadly')) {
                $reloadly = plugin_active('Reloadly');
                if ($reloadly->name == 'Reloadly') {
                    $reloadlyCredentials = JsonData::decodeArray($reloadly->data);
                    config()->set([
                        'reloadly.connections.client_id' => $reloadlyCredentials['client_id'] ?? null,
                        'reloadly.connections.client_secret' => $reloadlyCredentials['client_secret'] ?? null,
                        'reloadly.connections.live_or_sandbox_url' => $reloadlyCredentials['live_or_sandbox_url'] ?? null,
                    ]);
                }
            }

            // Flutterwave Plugin
            if (plugin_active('Flutterwave')) {
                $flutterwave = plugin_active('Flutterwave');
                if ($flutterwave->name == 'Flutterwave') {

                    $flutterwaveCredentials = JsonData::decodeArray($flutterwave->data);

                    config()->set([
                        'flutterwave.connections.secret_key' => $flutterwaveCredentials['secret_key'] ?? null,
                    ]);
                }
            }

            // Bloc Plugin
            if (plugin_active('Bloc')) {
                $bloc = plugin_active('Bloc');
                if ($bloc->name == 'Bloc') {

                    $blocCredentials = JsonData::decodeArray($bloc->data);

                    config()->set([
                        'bloc.connections.api_key' => $blocCredentials['api_key'] ?? null,
                    ]);
                }
            }

            // Tpaga Plugin
            if (plugin_active('Tpaga')) {
                $tpaga = plugin_active('Tpaga');
                if ($tpaga->name == 'Tpaga') {
                    $tpagaCredentials = JsonData::decodeArray($tpaga->data);
                    config()->set([
                        'tpaga.connections.api_key' => $tpagaCredentials['api_key'] ?? null,
                    ]);
                }
            }

            // Default plugin
            config()->set('sms.default', default_plugin('sms') ?? false);

            view()->composer(['frontend::auth.*', 'frontend::page*'], function ($view) {
                $googleReCaptcha = plugin_active('Google reCaptcha');
                View::share('googleReCaptcha', $googleReCaptcha);
            });

        }

    }
}
