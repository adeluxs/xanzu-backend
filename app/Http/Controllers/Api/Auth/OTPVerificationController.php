<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\NotifyTrait;
use App\Traits\SmsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class OTPVerificationController extends Controller
{
    use ApiResponse, NotifyTrait, SmsTrait;

    private const RESEND_COOLDOWN_SECONDS = 45;

    public function send(Request $request)
    {
        if (! setting_enabled('otp_verification', 'permission')) {
            return $this->errorResponse('OTP verification is disabled', 403);
        }

        $validation = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:30'],
            'dial_code' => ['required', 'exists:countries,dial_code'],
        ]);

        if ($validation->fails()) {
            return $this->validationErrorResponse($validation->errors());
        }

        $dialCode = trim((string) $request->dial_code);
        $localPhone = formatPhoneNumber((string) $request->phone, $dialCode, false, true);
        $fullPhone = formatPhoneNumber($localPhone, $dialCode, true, false);

        if (User::where('phone', $fullPhone)->exists()) {
            return $this->validationErrorResponse('Phone number already registered');
        }

        $existingOtp = PhoneOtp::query()
            ->where('phone', $localPhone)
            ->where('dial_code', $dialCode)
            ->latest('id')
            ->first();

        if ($existingOtp && ! $existingOtp->is_verified && $existingOtp->created_at) {
            $retryAt = $existingOtp->created_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS);
            if ($retryAt->isFuture()) {
                $retryAfter = $retryAt->diffInSeconds(now());
                $message = 'OTP already sent. Please wait '.$retryAfter.' seconds before requesting another code.';

                return $this->errorResponse(
                    $message,
                    429,
                    ['retry_after_seconds' => $retryAfter],
                    ['phone' => [$message]],
                    'RATE_LIMITED'
                );
            }
        }

        $isDemo = ! app()->isProduction() || (bool) env('APP_DEMO');
        $token = $isDemo ? 111111 : random_int(100000, 999999);

        if ($existingOtp) {
            $existingOtp->update([
                'otp' => $token,
                'expires_at' => Carbon::now()->addMinutes(10),
                'is_verified' => false,
            ]);
            $otpRecord = $existingOtp->fresh();
        } else {
            $otpRecord = PhoneOtp::create([
                'phone' => $localPhone,
                'otp' => $token,
                'expires_at' => Carbon::now()->addMinutes(10),
                'dial_code' => $dialCode,
                'is_verified' => false,
            ]);
        }

        $shortcodes = [
            '[[token]]' => $token,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
        ];

        if (! $isDemo) {
            $this->sendNotify(null, 'otp', 'User', $shortcodes, $dialCode.$localPhone, null);
        }

        return $this->successResponse([
            'otp' => ! $isDemo ? null : $token,
            'otp_id' => $otpRecord?->id,
        ], 'OTP sent successfully!');
    }

    public function verify(Request $request)
    {
        if (! setting_enabled('otp_verification', 'permission')) {
            return $this->errorResponse('OTP verification is disabled', 403);
        }

        $validate = Validator::make($request->all(), [
            'otp' => ['required', 'numeric', 'digits:6'],
            'phone' => ['required', 'string', 'max:30'],
            'dial_code' => ['required', 'exists:countries,dial_code'],
        ]);

        if ($validate->fails()) {
            return $this->validationErrorResponse($validate->errors());
        }

        $dialCode = trim((string) $request->dial_code);
        $localPhone = formatPhoneNumber((string) $request->phone, $dialCode, false, true);

        $otp = PhoneOtp::query()
            ->where('phone', $localPhone)
            ->where('dial_code', $dialCode)
            ->where('otp', (string) $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->where('is_verified', false)
            ->latest('id')
            ->first();

        if (! $otp) {
            return $this->validationErrorResponse('Invalid or expired OTP');
        }

        $otp->update(['is_verified' => true]);

        return $this->successResponse([
            'otp_id' => $otp->id,
        ], 'OTP verified successfully!');
    }
}
