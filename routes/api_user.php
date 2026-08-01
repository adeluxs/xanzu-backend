<?php

use App\Http\Controllers\Api\AddMoneyController;
use App\Http\Controllers\Api\CardApplicationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShippingAddressController;
use App\Http\Controllers\Api\SplitController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WithdrawAccountController;
use App\Http\Controllers\Api\WithdrawMoneyController;
use Illuminate\Support\Facades\Route;

Route::controller(DashboardController::class)->group(function () {
    Route::get('/', 'getUser');
});

Route::prefix('transactions')->controller(TransactionController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('card', 'cardTransactions');
    Route::get('main-wallet', 'mainWalletTransactions');
});

// Referral
Route::prefix('referral')->controller(ReferralController::class)->group(function () {
    Route::get('info', 'index');
    Route::get('direct', 'directReferrals');
    Route::get('tree', 'referralTree');
});

// Ticket
Route::apiResource('ticket', TicketController::class)->except('update', 'destroy');
Route::post('ticket/reply/{uuid}', [TicketController::class, 'reply']);
Route::post('ticket/close/{uuid}', [TicketController::class, 'close']);

// // Withdraw Account
Route::apiResource('withdraw-account', WithdrawAccountController::class)->only('index', 'store', 'update', 'destroy');
Route::post('withdraw', WithdrawMoneyController::class);

// Send Money / P2P Transfer
Route::controller(\App\Http\Controllers\Api\SendMoneyController::class)->prefix('transfer')->middleware('check_feature:user_transfer,kyc_user_transfer')->group(function () {
    Route::get('config', 'config');
    Route::get('lookup', 'lookupRecipient');
    Route::post('validate', 'validateTransferRequest');
    Route::post('/', 'store');
});

// Settings
Route::prefix('settings')->controller(SettingsController::class)->middleware('isDemo')->group(function () {
    Route::post('profile', 'profileUpdate');
    Route::post('2fa/{type}', 'twoFa');
    Route::post('account-close', 'accountClose');
    Route::post('change-password', 'updatePassword');
});

Route::get('kyc-histories', [KycController::class, 'histories']);
Route::apiResource('kyc', KycController::class)->only('index', 'store');

// Card Applications
Route::get('card-applications', [CardApplicationController::class, 'index']);
Route::post('card-applications', [CardApplicationController::class, 'store']);
Route::get('cards', [CardApplicationController::class, 'cards']);

// // Add Money
Route::controller(AddMoneyController::class)->prefix('add-money')->middleware('check_feature:buyer_deposit,kyc_buyer_deposit')->group(function () {
    Route::get('history', 'addMoneyHistory')->name('addMoney.history');
    Route::get('/', 'index')->name('addMoney');
    Route::post('/', 'store')->name('addMoney.now');
});

// Orders
Route::controller(OrderController::class)->prefix('orders')->middleware('check_feature:buyer_purchase,kyc_purchase')->group(function () {
    Route::get('/', 'index')->withoutMiddleware('check_feature:buyer_purchase,kyc_purchase');                        // GET  /api/user/orders
    Route::post('/', 'store');                       // POST /api/user/orders
    Route::get('upcoming-bnpl-installments', 'upcomingBnplInstallments'); // GET /api/user/orders/upcoming-bnpl-installments
    Route::get('seller', 'sellerOrders');            // GET  /api/user/orders/seller
    Route::post('{orderNumber}/cancel', 'cancel');   // POST /api/user/orders/{orderNumber}/cancel
    Route::get('{orderNumber}/bnpl', 'bnplItems');   // GET  /api/user/orders/{orderNumber}/bnpl
    Route::post('{orderNumber}/reviews', 'postReview'); // POST /api/user/orders/{orderNumber}/reviews
    Route::get('{orderNumber}/items/{orderItemId}/bnpl/installments', 'bnplInstallments');
    Route::post('{orderNumber}/items/{orderItemId}/bnpl/installments/{installmentId}/pay', 'payBnplInstallment');
    Route::get('{orderNumber}', 'show')->withoutMiddleware('check_feature:buyer_purchase,kyc_purchase');             // GET  /api/user/orders/{orderNumber}
    Route::post('topup', 'topup');                   // POST /api/user/orders/topup
});

// Splits
Route::get('splits', [SplitController::class, 'index']);

// Shipping Addresses (Address Book)
Route::apiResource('shipping-addresses', ShippingAddressController::class);
Route::post('shipping-addresses/{id}/set-default', [ShippingAddressController::class, 'setDefault']);