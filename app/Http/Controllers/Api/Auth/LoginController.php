<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginActivities;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $email = $request->input('email');
        $type = !$this->isEmail($email) ? 'username' : 'email';

        // Get the user by email or username
        $column = $type === 'email' ? 'email' : 'username';
        $user = User::where($column, $email)->where('user_type', 'buyer')->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request->email));

            return $this->validationErrorResponse(__('auth.failed'));
        }

        $this->ensureIsNotRateLimited($type, $request);

        RateLimiter::clear($this->throttleKey($request->email));

        $token = $user->createToken('auth_token')->plainTextToken;

        LoginActivities::add($user->id);

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()?->delete();

        return $this->successWithoutDataResponse(__('Logged out'));
    }

    private function isEmail($param)
    {
        return filter_var($param, FILTER_VALIDATE_EMAIL);
    }

    public function throttleKey($email)
    {
        return Str::transliterate(Str::lower($email) . '|' . request()->ip());
    }

    public function ensureIsNotRateLimited($type, Request $request)
    {
        $throttleKey = $this->throttleKey($request->email);
        if (!RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            $type => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}
