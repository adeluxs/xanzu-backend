<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    use ApiResponse, NotifyTrait;

    private const OTP_TTL_MINUTES = 15;

    public function sendResetOtpEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $email = strtolower(trim((string) $request->email));
        $token = random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => (string) $token, 'created_at' => Carbon::now()]
        );

        $url = route('password.reset', ['token' => $token, 'email' => $email]);
        $shortcodes = [
            '[[token]]' => $token,
            '[[reset_url]]' => $url,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
        ];

        $this->sendNotify($email, 'forgot_password_otp', 'User', $shortcodes, null, null);

        return $this->successResponse([
            'otp' => app()->isProduction() ? null : $token,
        ], __('We have emailed your password reset OTP!'));
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $record = $this->validOtp(strtolower(trim((string) $request->email)), (string) $request->otp);
        if (! $record) {
            return $this->validationErrorResponse('Invalid or expired OTP');
        }

        return $this->successWithoutDataResponse('OTP verified successfully');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'digits:6'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $email = strtolower(trim((string) $request->email));
        if (! $this->validOtp($email, (string) $request->otp)) {
            return $this->validationErrorResponse('Invalid or expired OTP');
        }

        User::where('email', $email)->update(['password' => Hash::make((string) $request->password)]);
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return $this->successWithoutDataResponse('Password reset successfully');
    }

    private function validOtp(string $email, string $otp): ?object
    {
        return DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $otp)
            ->where('created_at', '>=', Carbon::now()->subMinutes(self::OTP_TTL_MINUTES))
            ->first();
    }
}
