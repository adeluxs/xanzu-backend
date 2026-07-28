<?php

use App\Http\Controllers\Api\Merchant\ApiManagementController;
use App\Http\Controllers\Api\Merchant\AuthController as MerchantAuthController;
use App\Http\Controllers\Api\Merchant\BnplCheckoutSessionController;
use App\Http\Controllers\Api\Merchant\DashboardController;
use App\Http\Controllers\Api\Merchant\KycController;
use App\Http\Controllers\Api\Merchant\ListingController;
use App\Http\Controllers\Api\Merchant\OrderController as MerchantOrderController;
use App\Http\Controllers\Api\Merchant\SettingsController;
use App\Http\Controllers\Api\Merchant\StoreManagementController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProviderProductController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WithdrawAccountController;
use App\Http\Controllers\Api\WithdrawMoneyController;
use App\Http\Controllers\Api\SendMoneyController;
use Illuminate\Support\Facades\Route;







Route::prefix('merchant')->group(function () {
    Route::prefix('bnpl')->controller(BnplCheckoutSessionController::class)->group(function () {
        Route::post('checkout-session', 'checkoutSession');
        Route::post('order-status', 'syncOrderStatus');
    });

    Route::prefix('sandbox/bnpl')->controller(BnplCheckoutSessionController::class)->group(function () {
        Route::post('checkout-session', 'checkoutSession');
        Route::post('order-status', 'syncOrderStatus');
    });

    Route::prefix('auth')->controller(MerchantAuthController::class)->group(function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
        Route::post('forgot-password', 'sendResetOtpEmail');
        Route::post('reset-verify-otp', 'verifyResetOtp');
        Route::post('reset-password', 'resetPassword');
        Route::post('resubmit', 'resubmit')->middleware('auth:sanctum');
        Route::post('logout', 'logout')->middleware('auth:sanctum');
        Route::post('email-otp/send', 'sendEmailOtp')->middleware('auth:sanctum');
        Route::post('email-otp/verify', 'verifyEmailOtp')->middleware('auth:sanctum');
    });


    Route::middleware('auth:sanctum')->name('merchant.')->group(function () {
        Route::controller(DashboardController::class)->group(function () {
            Route::get('/', 'getUser');
            Route::get('dashboard', 'index');
        });

        Route::prefix('transactions')->controller(TransactionController::class)->group(function () {
            Route::get('/', 'index');
        });

        Route::prefix('orders')->controller(MerchantOrderController::class)->group(function () {
            Route::get('pending-count', 'pendingCount');
            Route::get('items', 'items');
            Route::get('items/{id}', 'itemDetails');
            Route::post('items/{id}/status', 'updateItemStatus');
            Route::get('items/{id}/delivery-items', 'deliveryItems');
            Route::post('items/{id}/delivery-items', 'storeDeliveryItems');
            Route::post('items/{id}/delivery-items/update', 'updateDeliveryItem');
        });

        Route::get('listings/{id}/delivery-items', [ListingController::class, 'deliveryItems']);
        Route::post('listings/{id}/delivery-items', [ListingController::class, 'storeDeliveryItems']);
        Route::apiResource('listings', ListingController::class)->only('index', 'store', 'show', 'destroy');
        Route::post('listings/{id}', [ListingController::class, 'update'])->middleware('isDemo');

        // Ticket
        Route::apiResource('ticket', TicketController::class)->except('update', 'destroy');
        Route::post('ticket/reply/{uuid}', [TicketController::class, 'reply']);
        Route::post('ticket/close/{uuid}', [TicketController::class, 'close']);

        // Notifications
        Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
            Route::get('/', 'getNotifications');
            Route::post('read', 'markAsRead');
            Route::post('setup-fcm', 'registerDevice');
        });

        // Withdraw Account
        Route::apiResource('withdraw-account', WithdrawAccountController::class)->only('index', 'show', 'store', 'update', 'destroy');
        Route::post('withdraw', WithdrawMoneyController::class);

        // Send Money / P2P Transfer
        Route::controller(SendMoneyController::class)->prefix('transfer')->group(function () {
            Route::get('config', 'config');
            Route::post('validate', 'validateTransferRequest');
            Route::post('/', 'store');
        });

        // Settings
        Route::prefix('settings')->controller(SettingsController::class)->middleware('isDemo')->group(function () {
            Route::post('profile', 'profileUpdate');
            Route::post('2fa', 'twoFa');
            Route::post('account-close', 'accountClose');
            Route::post('change-password', 'updatePassword');
        });

        // Store Management
        Route::prefix('store-management')->controller(StoreManagementController::class)->group(function () {
            Route::get('provider', 'provider');
            Route::post('provider', 'upsertProvider')->middleware('isDemo');
        });

        // API Management
        Route::prefix('api-management')->controller(ApiManagementController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('generate', 'generate')->middleware('isDemo');
        });

        Route::get('kyc-histories', [KycController::class, 'histories']);

        // Provider Products (WooCommerce)
        Route::prefix('provider-products')->controller(ProviderProductController::class)->group(function () {
            Route::get('search', 'search');
            Route::get('config', 'config');
            Route::post('import', 'import');
            Route::get('products', 'products');
            Route::delete('product/delete/{id}', 'delete');
            Route::get('orders', 'orders');
        });
    });

});
