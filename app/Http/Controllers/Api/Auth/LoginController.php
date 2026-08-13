<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginActivities;
use App\Models\User;
use App\Support\Auth\MobileAuthPayload;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->first());
        }

        $identifier = trim((string) $request->input('email'));
        $type = $this->isEmail($identifier) ? 'email' : 'username';
        if ($type === 'email') {
            $identifier = Str::lower($identifier);
        }

        try {
            $this->ensureIsNotRateLimited($type, $request, $identifier);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($exception->errors());
        }

        $user = User::query()
            ->where($type, $identifier)
            ->where('user_type', 'buyer')
            ->first();

        if (! $user || ! Hash::check((string) $request->password, (string) $user->password)) {
            RateLimiter::hit($this->throttleKey($identifier, $request));

            return $this->validationErrorResponse(__('auth.failed'));
        }

        if ((int) $user->status === 0) {
            return $this->errorResponse(__('Your account is disabled. Please contact support.'), 403);
        }

        if ((int) $user->status === 2) {
            return $this->errorResponse(__('Your account is closed. Please contact support if you want to restore it.'), 403);
        }

        RateLimiter::clear($this->throttleKey($identifier, $request));

        $token = $user->createToken('auth_token')->plainTextToken;
        LoginActivities::add($user->id);

        return $this->successResponse(
            MobileAuthPayload::make($user, $token),
            __('Login successful.')
        );
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successWithoutDataResponse(__('Logged out'));
    }

    private function isEmail(string $param): bool
    {
        return filter_var($param, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function throttleKey(string $identifier, Request $request): string
    {
        return Str::transliterate(Str::lower($identifier).'|'.$request->ip());
    }

    private function ensureIsNotRateLimited(string $type, Request $request, string $identifier): void
    {
        $throttleKey = $this->throttleKey($identifier, $request);
        if (! RateLimiter::tooManyAttempts($throttleKey, 5)) {
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
