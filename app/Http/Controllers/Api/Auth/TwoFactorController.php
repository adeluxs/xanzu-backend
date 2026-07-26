<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');

        if (setting('fa_verification', 'permission') != 'enabled') {
            return $this->validationErrorResponse('Two factor authentication verification is disabled');
        } elseif (! $user->two_fa) {
            return $this->validationErrorResponse('Two factor authentication is not enabled for this user');
        }

        // Check code is valid
        try {
            if (! $google2fa->verifyKey($user->google2fa_secret, $request->code)) {
                return $this->validationErrorResponse('The provided 2FA code is invalid');
            }
        } catch (\Throwable $th) {
            // throw $th;
            return $this->validationErrorResponse('An error occurred while verifying the 2FA code');
        }

        return $this->successWithoutDataResponse('2FA verification successful');
    }
}
