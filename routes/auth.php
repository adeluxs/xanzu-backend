<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OtpVerifyController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\ForgetPasswordController;
use Illuminate\Support\Facades\Route;

// ================================ User Auth Section ================================

Route::middleware(['guest:web', 'isDemo'])->group(function () {

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])->withoutMiddleware(['isDemo'])
        ->name('login.now');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.update');
});

Route::middleware(['auth:web', 'isDemo'])->group(function () {
    Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::any('logout', [AuthenticatedSessionController::class, 'destroy'])->withoutMiddleware(['isDemo'])
        ->name('logout');

    // Verify OTP
    Route::get('verify/otp', [OtpVerifyController::class, 'index'])->name('otp.verify');
    Route::get('resend/otp', [OtpVerifyController::class, 'resend'])->name('otp.resend');
    Route::post('/verify', [OtpVerifyController::class, 'verify'])->name('otp.verify.post');
});

// ================================ Admin Auth Section ================================

Route::group(['prefix' => setting('site_admin_prefix', 'global'), 'as' => 'admin.'], function () {
    Route::get('login', [AuthController::class, 'loginView'])->name('login-view');
    Route::post('login', [AuthController::class, 'authenticate'])->name('login');

    // Forget Password
    Route::get('forget-password', [ForgetPasswordController::class, 'showForgetPasswordForm'])->name('forget.password.now');
    Route::post('forget-password', [ForgetPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.submit')->withoutMiddleware('isDemo');
    Route::get('reset-password/{token}', [ForgetPasswordController::class, 'showResetPasswordForm'])->name('reset.password.now');
    Route::post('reset-password', [ForgetPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.submit')->withoutMiddleware('isDemo');
});
