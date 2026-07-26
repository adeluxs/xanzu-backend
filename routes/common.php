<?php

use App\Http\Controllers\Frontend\ChatController;
use App\Http\Controllers\Frontend\CouponController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\DepositController;
use App\Http\Controllers\Frontend\GatewayController;
use App\Http\Controllers\Frontend\KycController;
use App\Http\Controllers\Frontend\ListingController;
use App\Http\Controllers\Frontend\PaymentController;
use App\Http\Controllers\Frontend\PurchaseController;
use App\Http\Controllers\Frontend\ReferralController;
use App\Http\Controllers\Frontend\SellController;
use App\Http\Controllers\Frontend\SettingController;
use App\Http\Controllers\Frontend\SubscriptionController;
use App\Http\Controllers\Frontend\TicketController;
use App\Http\Controllers\Frontend\TopupController;
use App\Http\Controllers\Frontend\TransactionController;
use App\Http\Controllers\Frontend\UserController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Middleware\HasValidSubscriptionMiddleware;
use App\Http\Middleware\SellerHasKycMiddleware;
use Illuminate\Support\Facades\Route;

// User Part
// Dashboard
Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

// Subscription
Route::prefix('subscriptions')->controller(SubscriptionController::class)->group(function () {
    Route::get('/', 'index')->name('subscriptions');
    Route::get('/history', 'history')->name('subscriptions.history')->middleware(SellerHasKycMiddleware::class);
    Route::get('/purchase/{plan}', 'purchasePreview')->name('subscription.purchase.preview');
    Route::post('/subscription-now', 'subscriptionNow')->name('subscription.now');
});

// User Notify
Route::get('notify', [UserController::class, 'notifyUser'])->name('notify');
Route::get('notification/all', [UserController::class, 'allNotification'])->name('notification.all');
Route::get('latest-notification', [UserController::class, 'latestNotification'])->name('latest-notification');
Route::get('notification-read/{id}', [UserController::class, 'readNotification'])->name('read-notification');

// Change Password
Route::get('/change-password', [UserController::class, 'changePassword'])->name('change.password');
Route::post('/password-store', [UserController::class, 'newPassword'])->middleware('isDemo')->name('new.password');

// KYC Apply
Route::get('kyc', [KycController::class, 'kyc'])->name('kyc');
Route::get('kyc-details', [KycController::class, 'kycDetails'])->name('kyc.details');
Route::get('kyc/submission/{id}', [KycController::class, 'kycSubmission'])->name('kyc.submission');
Route::get('kyc/{id}', [KycController::class, 'kycData'])->name('kyc.data');
Route::post('kyc-submit', [KycController::class, 'submit'])->middleware('isDemo')->name('kyc.submit');

// Listing

Route::prefix('listing')->name('listing.')->middleware([
    SellerHasKycMiddleware::class,
    HasValidSubscriptionMiddleware::class,
])->group(function () {
    Route::get('/', [ListingController::class, 'index'])->withoutMiddleware(HasValidSubscriptionMiddleware::class)->name('index');
    Route::match(['get', 'post'], '/create/{step?}/{id?}', [ListingController::class, 'create'])->name('create');
    Route::match(['get', 'post'], '/edit/{step?}/{id?}', [ListingController::class, 'create'])->name('edit');
    Route::post('/store', [ListingController::class, 'store'])->middleware(['XSS', 'isDemo'])->name('store');
    Route::get('/delivery-items/{id}', [ListingController::class, 'deliveryItems'])->name('delivery-items');
    Route::post('/delivery-items/{id}/store', [ListingController::class, 'deliveryItemsStore'])->name('delivery-items.store');
    Route::get('/delete/{id}', [ListingController::class, 'destroy'])->name('delete');
    Route::get('/gallery/delete/{id}', [ListingController::class, 'galleryDelete'])->name('gallery.delete');

    Route::get('sub-category/{category}', [ListingController::class, 'getSubCatHtml'])->name('get.sub.cat.html');
});

// Coupon

Route::name('coupon.')->prefix('coupon')->middleware([
    SellerHasKycMiddleware::class,
])->group(function () {
    Route::get('', [CouponController::class, 'index'])->name('index');
    Route::get('create', [CouponController::class, 'create'])->name('create');
    Route::post('store', [CouponController::class, 'store'])->middleware('isDemo')->name('store');
    Route::get('{id}/edit', [CouponController::class, 'edit'])->name('edit');
    Route::post('{id}/update', [CouponController::class, 'update'])->middleware('isDemo')->name('update');
    Route::get('{id}/delete', [CouponController::class, 'destroy'])->name('delete');
});

// Wishlist

Route::name('wishlist.')->prefix('wishlist')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
});

// Purchase
Route::name('purchase.')->prefix('purchase')->group(function () {
    Route::get('/', [PurchaseController::class, 'index'])->name('index');
    Route::get('/success/{order:order_number}', [PurchaseController::class, 'success'])->name('success');
    Route::get('/invoice/{order:order_number}', [PurchaseController::class, 'invoice'])->name('invoice');
    Route::get('/deliveryItems/{order:order_number}', [PurchaseController::class, 'deliveryItems'])->name('deliveryItems');
    Route::post('/review', action: [PurchaseController::class, 'storeReview'])->middleware(['XSS', 'isDemo'])->name('review.store');
});

// Sold
Route::name('sell.')->middleware([
    SellerHasKycMiddleware::class,
])->prefix('sell')->group(function () {
    Route::get('/', [SellController::class, 'index'])->name('index');
    Route::get('/refund/{order:order_number}', [SellController::class, 'refund'])->name('refund');
});

Route::middleware([
    'userPermissionChecker:user_type_deposit,deposit_status',
    'userPermissionChecker:kyc_verification',
])->group(function () {
    // Topup
    Route::name('topup.')->prefix('topup')->group(function () {
        Route::get('/', [TopupController::class, 'index'])->name('index');
        Route::post('/', [TopupController::class, 'purchase'])->middleware(['XSS', 'isDemo'])->name('purchase');
    });

    // Deposit
    Route::name('deposit.')->prefix('deposit')->group(function () {
        Route::get('/', [TopupController::class, 'index'])->name('index');
        Route::post('/', [TopupController::class, 'purchase'])->middleware(['XSS', 'isDemo'])->name('purchase');
    });
});

// Chat

Route::name('chat.')->prefix('chat')->group(function () {
    Route::get('/{username?}', [ChatController::class, 'index'])->name('index');
    Route::post('store/{receiver:username}', [ChatController::class, 'store'])->middleware('XSS')->name('store');
});

// Transactions
Route::get('transactions/{type?}', [TransactionController::class, 'transactions'])->name('transactions');

// Deposit
Route::group(['prefix' => 'deposit', 'as' => 'deposit.'], function () {
    Route::get('gateway/{code}', [GatewayController::class, 'gateway'])->name('gateway');
    Route::post('store', [DepositController::class, 'store'])->middleware('isDemo')->middleware('XSS')->name('store');
});

// Payment Controller
Route::prefix('payment')->name('payment.')->controller(PaymentController::class)->middleware([
    'userPermissionChecker:user_type_withdraw,withdraw_status',
])->group(function () {

    Route::get('/', 'index')->name('index');

    Route::prefix('payout')->name('withdraw.')->group(function () {
        Route::get('/', 'index')->name('index');

        Route::get('method/{id}', 'withdrawMethod')->name('method');
        Route::post('now', 'withdrawNow')->middleware('isDemo')->name('now');
        Route::get('details/{accountId}/{amount?}', 'details')->name('details');

        // Withdraw Account
        Route::prefix('account')->name('account.')->group(function () {
            Route::get('/', 'account')->name('index');
            Route::post('store', 'store')->name('store');
            Route::get('edit/{id}', 'edit')->name('edit');
            Route::post('update/{id}', 'update')->name('update');
            Route::post('delete/{id}', 'delete')->name('delete');
            Route::get('method/{id}', 'withdrawMethod')->name('method');

        });
    });

});

// Support ticket
Route::group(['prefix' => 'support-ticket', 'as' => 'ticket.', 'controller' => TicketController::class], function () {
    Route::get('index', 'index')->name('index');
    Route::post('store', 'store')->middleware('XSS')->name('store');
    Route::post('reply', 'reply')->middleware('XSS')->name('reply');
    Route::get('show/{uuid}', 'show')->name('show');
    Route::get('close-now/{uuid}', 'closeNow')->name('close.now');
});

// Referral
Route::get('referral', [ReferralController::class, 'referral'])->name('referral');
Route::get('referral/tree', [ReferralController::class, 'referralTree'])->name('referral.tree');
Route::get('referral/generate', [ReferralController::class, 'generateReferralLinks'])->name('referral.generate');

// Follower-Following list

Route::name('follow-list.')->group(function () {
    Route::get('following', [SettingController::class, 'following'])->name('following');
    Route::get('followers', [SettingController::class, 'followers'])->name('followers');
});

// Settings
Route::group(['prefix' => 'settings', 'as' => 'setting.', 'controller' => SettingController::class], function () {
    Route::get('/', 'settings')->name('show');
    Route::get('2fa', 'twoFa')->name('2fa');
    Route::get('action', 'action')->name('action');
    Route::post('action-2fa', 'actionTwoFa')->middleware('isDemo')->name('action-2fa');
    Route::post('profile-update', 'profileUpdate')->middleware(['XSS', 'isDemo'])->name('profile-update');
    Route::post('close-account', 'closeAccount')->middleware('isDemo')->name('close.account');
    Route::get('privacy', 'privacy')->name('privacy');
    Route::post('privacy-update', 'updatePrivacySetting')->middleware('isDemo')->name('privacy.update');

    Route::post('/2fa/verify', function () {
        return redirect(route('user.dashboard'));
    })->name('2fa.verify');
});
