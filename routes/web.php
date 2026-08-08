<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\Frontend\BnplCheckoutController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\IpnController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\RayplusmoneyController;
use App\Http\Controllers\Frontend\StatusController;
use Illuminate\Support\Facades\Route;




Route::get('/', [HomeController::class, 'home'])->name('home');
// BNPL (WordPress)
Route::match(['get', 'post'], 'bnpl/auth', [BnplCheckoutController::class, 'auth'])->name('bnpl.auth');
Route::middleware('auth')->group(function () {
    Route::get('bnpl/process', [BnplCheckoutController::class, 'process'])->name('bnpl.process');
    Route::post('bnpl/confirm', [BnplCheckoutController::class, 'confirm'])->name('bnpl.confirm');
    Route::get('bnpl/cancel', [BnplCheckoutController::class, 'cancel'])->name('bnpl.cancel');
});

// Gateway status
Route::withoutMiddleware(['auth'])->controller(StatusController::class)->prefix('status')->name('status.')->group(function () {
    Route::match(['get', 'post'], '/success', 'success')->name('success');
    Route::match(['get', 'post'], '/cancel', 'cancel')->name('cancel');
    Route::match(['get', 'post'], '/pending', 'pending')->name('pending');
});

// RayPlusMoney hosted checkout return routes. These endpoints never trust the
// browser redirect itself; they verify the provider token before changing a
// wallet balance.
Route::withoutMiddleware(['auth'])->controller(RayplusmoneyController::class)
    ->prefix('status/rayplusmoney')->name('status.rayplusmoney.')->group(function () {
        Route::match(['get', 'post'], 'success', 'success')->name('success');
        Route::match(['get', 'post'], 'cancel', 'cancel')->name('cancel');
    });

// Instant payment notification
Route::group(['prefix' => 'ipn', 'as' => 'ipn.', 'controller' => IpnController::class], function () {
    Route::post('coinpayments', 'coinpaymentsIpn')->name('coinpayments');
    Route::post('nowpayments', 'nowpaymentsIpn')->name('nowpayments');
    Route::post('cryptomus', 'cryptomusIpn')->name('cryptomus');
    Route::get('paypal', 'paypalIpn')->name('paypal');
    Route::post('mollie', 'mollieIpn')->name('mollie');
    Route::any('perfectmoney', 'perfectMoneyIpn')->name('perfectMoney');
    Route::get('paystack', 'paystackIpn')->name('paystack');
    Route::get('flutterwave', 'flutterwaveIpn')->name('flutterwave');
    Route::post('coingate', 'coingateIpn')->name('coingate');
    Route::get('monnify', 'monnifyIpn')->name('monnify');
    Route::get('non-hosted-securionpay', 'nonHostedSecurionpayIpn')->name('non-hosted.securionpay')->middleware(['auth', 'XSS']);
    Route::post('coinremitter', 'coinremitterIpn')->name('coinremitter');
    Route::post('btcpay', 'btcpayIpn')->name('btcpay');
    Route::post('binance', 'binanceIpn')->name('binance');
    Route::get('blockchain', 'blockchainIpn')->name('blockchain');
    Route::get('instamojo', 'instamojoIpn')->name('instamojo');
    Route::post('paytm', 'paytmIpn')->name('paytm');
    Route::post('razorpay', 'razorpayIpn')->name('razorpay');
    Route::post('twocheckout', 'twocheckoutIpn')->name('twocheckout');
    Route::post('stripe', 'stripeWebhook')->name('stripe');
    Route::post('rayplusmoney', 'rayplusmoneyIpn')->name('rayplusmoney');
});

// Translate
Route::get('language-update', [HomeController::class, 'languageUpdate'])->name('language-update');


// Site cron job
Route::get('notification-tune', [AppController::class, 'notificationTune'])->name('notification-tune');
Route::get('site-cron', [CronJobController::class, 'runCronJobs'])->middleware('isDemo')->name('cron.job');

// Dynamic Page
Route::get('page/{section}', [PageController::class, 'getPage'])->name('dynamic.page');
Route::get('{section}', [PageController::class, 'getPage'])->where('section', '^(?!admin$|admin/|api|login|register).*$')
    ->name('page');
