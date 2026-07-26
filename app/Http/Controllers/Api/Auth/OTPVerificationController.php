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
use Validator;

class OTPVerificationController extends Controller
{
    use ApiResponse, NotifyTrait, SmsTrait;

    public function send(Request $request)
    {
        if (! setting('otp_verification', 'permission')) {
            return $this->validationErrorResponse('OTP verification is disabled');
        }

        // validate re request if before expires

        if (User::where('phone', $request->dial_code.$request->phone)->exists()) {
            return $this->validationErrorResponse('Phone number already registered');
        } elseif (PhoneOtp::where('phone', $request->phone)->where('dial_code', $request->dial_code)->where('expires_at', '>', Carbon::now())->exists()) {
            return $this->validationErrorResponse('OTP already sent. Please wait before requesting again.');
        }

        $validation = Validator::make($request->all(), [
            'phone' => 'required|string',
            'dial_code' => 'required|exists:countries,dial_code',
        ]);

        if ($validation->fails()) {
            return $this->validationErrorResponse($validation->errors()->first());
        }

        $phone = formatPhoneNumber($request->phone, $request->dial_code, false, true);

        $isDemo = ! app()->isProduction() || env('APP_DEMO');

        $token = $isDemo ? 111111 : random_int(100000, 999999);

        PhoneOtp::insert(
            [
                'phone' => $phone,
                'otp' => $token,
                'expires_at' => Carbon::now()->addMinutes(10),
                'dial_code' => $request->dial_code,
            ]
        );

        $shortcodes = [
            '[[token]]' => $token,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
        ];

        if (! $isDemo) {
            $this->sendNotify(null, 'otp', 'User', $shortcodes, $request->dial_code.$phone, null);
        }

        return $this->successResponse(['otp' => ! $isDemo ? null : $token], 'OTP sent successfully!');

    }

    public function verify(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'phone' => 'required|string|exists:phone_otps,phone',
            'dial_code' => 'required|exists:countries,dial_code|exists:phone_otps,dial_code',
        ]);

        if ($validate->fails()) {
            return $this->validationErrorResponse($validate->errors()->first());
        }

        $otp = PhoneOtp::where('phone', $request->phone)
            ->where('dial_code', $request->dial_code)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->where('is_verified', false)
            ->first();

        if (! $otp) {
            return $this->validationErrorResponse('Invalid otp');
        }
        $otp->update([
            'is_verified' => true,
        ]);

        return $this->successResponse([
            'otp_id' => $otp->id,
        ], 'OTP verified successfully!');
    }
}
