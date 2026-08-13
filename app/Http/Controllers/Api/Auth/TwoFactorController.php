<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TwoFactorController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|digits:6',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');

        if (! setting_enabled('fa_verification', 'permission')) {
            return $this->errorResponse('Two factor authentication verification is disabled', 403);
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
