<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmailVerificationController extends Controller
{
    use ApiResponse, NotifyTrait;

    public function sendVerifyEmail(Request $request)
    {
        if (! setting('email_verification', 'permission')) {
            return $this->validationErrorResponse('Email verification is disabled');
        }
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return $this->validationErrorResponse('Email already verified');
        } else {
            $token = random_int(100000, 999999);

            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);

            $shortcodes = [
                '[[token]]' => $token,
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_url]]' => route('home'),
                '[[full_name]]' => $user->full_name,
            ];

            $this->sendNotify($user->email, 'email_verification_otp', 'User', $shortcodes, null, null);

            return $this->successResponse(['otp' => app()->isProduction() ? null : $token], 'Email verification mail sent successfully!');
        }

    }

    public function verify(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->validationErrorResponse('Email already verified');
        }

        $user = $request->user();

        $updatePassword = DB::table('password_reset_tokens')
            ->where([
                'email' => $user->email,
                'token' => $request->otp,
            ])
            ->first();

        if (! $updatePassword) {
            return $this->validationErrorResponse('Invalid otp');
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
