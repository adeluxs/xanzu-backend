<?php

use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\OTPVerificationController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\TwoFactorController;
use App\Http\Controllers\Api\GeneralController;
use App\Http\Controllers\Api\HomeScreenController;
use App\Http\Controllers\Api\LandingController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

// General Controller
Route::controller(GeneralController::class)->group(function () {
    Route::get('get-countries', 'getCountries');

    Route::get('get-settings', 'getSettings');
    Route::get('get-languages', 'getLanguages');
    Route::get('get-register-fields/{type?}', 'getRegisterFields');
    Route::get('get-app-splash-onboarding-screen', 'getAppSplashOnboardingScreen');
});

// App Home Screen
Route::get('home', [HomeScreenController::class, 'index']);
Route::get('product-sections', [HomeScreenController::class, 'productSections']);
Route::get('coupon/{code}', [HomeScreenController::class, 'couponByCode']);
Route::get('products', [HomeScreenController::class, 'listingFilter']);
Route::get('filter-data', [HomeScreenController::class, 'filterData']);
Route::get('product/{id}', [HomeScreenController::class, 'productDetails']);
Route::get('product/{id}/reviews', [HomeScreenController::class, 'productReviews']);

// Categories & Brands
Route::get('trending-categories', [HomeScreenController::class, 'getTrendingCategories']);
Route::get('popular-brands', [HomeScreenController::class, 'getPopularBrands']);
Route::get('popular-providers', [HomeScreenController::class, 'getPopularProviders']);
Route::get('provider/{id}', [HomeScreenController::class, 'providerDetails']);


Route::get('landing-data/{lang?}/{code?}', [LandingController::class, 'index']);
Route::get('page-data/{lang?}/{code?}', [LandingController::class, 'pages']);
Route::get('navigation/{lang?}', [LandingController::class, 'navigation']);

// General Controller (Authenticated)
Route::controller(GeneralController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('get-plugins', 'getPlugins');
    Route::get('get-transaction-types', 'getTransactionTypes');
    Route::get('get-withdraw-methods', 'getWithdrawMethods');
    Route::get('get-notifications', 'getNotifications');
    Route::get('mark-as-read-notification/{id?}', 'markNotificationAsRead');

    // FCM Notification
    Route::controller(NotificationController::class)->group(function () {
        Route::post('setup-fcm', 'registerDevice');
    });
});

// Language
Route::get('change-language/{locale}', [LanguageController::class, 'changeLanguage']);

Route::middleware(['auth:sanctum', 'throttle:6,1'])->group(function () {
    // email verification
    Route::controller(EmailVerificationController::class)->prefix('email/verify')->group(function () {
        Route::post('/', 'verify');
        Route::post('email-send', 'sendVerifyEmail');
    });

});

// Auth Controller
Route::controller(LoginController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('logout', 'logout')->middleware('auth:sanctum');
});

// Register OTP Verification
Route::controller(OTPVerificationController::class)->group(function () {
    Route::post('register/otp/verify', 'verify');
    Route::post('register/otp/send', 'send');
});

// Register
Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'store');
});

// Forgot Password
Route::controller(ForgotPasswordController::class)->group(function () {
    Route::post('forgot-password', 'sendResetOtpEmail');
    Route::post('reset-verify-otp', 'verifyOtp');
    Route::post('reset-password', 'resetPassword');
});

// 2fa
Route::post('2fa/verify', TwoFactorController::class)->middleware('auth:sanctum');

// user panel
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    require __DIR__ . '/api_user.php';
});

require __DIR__ . '/api_merchant.php';
