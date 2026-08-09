<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmailVerificationController extends Controller
{
    use ApiResponse, NotifyTrait;

    private const OTP_TTL_MINUTES = 15;

    public function sendVerifyEmail(Request $request)
    {
        if (! setting('email_verification', 'permission')) {
            return $this->validationErrorResponse('Email verification is disabled');
        }

        $user = $request->user();
        if (! $user) {
            return $this->unauthorizedResponse();
        }
        if ($user->hasVerifiedEmail()) {
            return $this->successWithoutDataResponse('Email already verified');
        }

        $token = random_int(100000, 999999);

        // password_reset_tokens.email is a primary key on many installations.
        // A plain insert caused duplicate-key failures after password reset or
        // a previous verification attempt, making login/signup appear stuck.
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => (string) $token, 'created_at' => Carbon::now()]
        );

        $shortcodes = [
            '[[token]]' => $token,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[full_name]]' => $user->full_name,
        ];

        $this->sendNotify($user->email, 'email_verification_otp', 'User', $shortcodes, null, null);

        return $this->successResponse(
            ['otp' => app()->isProduction() ? null : $token],
            'Email verification mail sent successfully!'
        );
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'numeric', 'digits:6'],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->first());
        }

        $user = $request->user();
        if (! $user) {
            return $this->unauthorizedResponse();
        }
        if ($user->hasVerifiedEmail()) {
            return $this->successWithoutDataResponse('Email already verified');
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->where('token', (string) $request->otp)
            ->where('created_at', '>=', Carbon::now()->subMinutes(self::OTP_TTL_MINUTES))
            ->first();

        if (! $record) {
            return $this->validationErrorResponse('Invalid or expired OTP');
        }

        if ($user->markEmailAsVerified()) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            $refUser = User::find($user->ref_id);
            if ($refUser) {
                app(RegisterController::class)->processReferralBonus($refUser, $user);
            }
        }

        return $this->successWithoutDataResponse('Email verified successfully!');
    }
}
