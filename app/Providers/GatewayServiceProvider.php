<?php

namespace App\Providers;

use App\Support\Performance\DatabaseAvailability;
use Illuminate\Support\ServiceProvider;
use Mollie\Laravel\Facades\Mollie;

class GatewayServiceProvider extends ServiceProvider
{
    /**
     * Register modules.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap modules.
     *
     * @return void
     */
    public function boot()
    {

        if (DatabaseAvailability::tableExists('gateways')) {
            // =============== paypal ==============
            $paypalCredential = gateway_info('paypal');
            $paystackCredential = gateway_info('paystack');
            $perfectmoneyCredential = gateway_info('perfectmoney');
            $coinbaseCredential = gateway_info('coinbase');

            if ($paypalCredential) {
                $mode = (string) ($paypalCredential->mode ?? 'sandbox');
                $paypalInfo = ['paypal.mode' => $mode];

                if ($mode !== 'sandbox') {
                    $paypalInfo = array_merge($paypalInfo, [
                        'paypal.live.app_id' => $paypalCredential->app_id ?? '',
                        'paypal.live.client_id' => $paypalCredential->client_id ?? '',
                        'paypal.live.client_secret' => $paypalCredential->client_secret ?? '',
                    ]);
                } else {
                    $paypalInfo = array_merge($paypalInfo, [
                        'paypal.sandbox.app_id' => $paypalCredential->app_id ?? '',
                        'paypal.sandbox.client_id' => $paypalCredential->client_id ?? '',
                        'paypal.sandbox.client_secret' => $paypalCredential->client_secret ?? '',
                    ]);
                }

                config()->set($paypalInfo);
            }

            // =============== Mollie ==============
            $mollieCredential = gateway_info('mollie');
            if ($mollieCredential) {
                Mollie::api()->setApiKey($mollieCredential->api_key ?? '');
            }

            // =============== Paystack ==============
            if ($paystackCredential) {
                config()->set([
                    'paystack.publicKey' => $paystackCredential->public_key ?? '',
                    'paystack.merchantEmail' => $paystackCredential->merchant_email ?? '',
                    'paystack.secretKey' => $paystackCredential->secret_key ?? '',
                ]);
            }

            // =============== Perfect Money ==============
            if ($perfectmoneyCredential) {
                config()->set([
                    'perfectmoney.account_id' => $perfectmoneyCredential->PM_ACCOUNTID ?? '',
                    'perfectmoney.passphrase' => $perfectmoneyCredential->PM_PASSPHRASE ?? '',
                    'perfectmoney.marchant_id' => $perfectmoneyCredential->PM_MARCHANTID ?? '',
                ]);
            }

            // =============== Coinbase ==============
            if ($coinbaseCredential) {
                config()->set([
                    'coinbase.apiKey' => $coinbaseCredential->apiKey ?? '',
                    'coinbase.webhookSecret' => $coinbaseCredential->webhookSecret ?? '',
                    'coinbase.apiVersion' => $coinbaseCredential->apiVersion ?? '',
                ]);
            }
        }
    }
}
