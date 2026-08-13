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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $requestId = (string) ($request->attributes->get('request_id') ?: Str::uuid());
        $request->attributes->set('request_id', $requestId);

        Log::info('MOBILE LOGIN REQUEST RECEIVED', [
            'request_id' => $requestId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'has_identifier' => $request->filled('email'),
            'has_password' => $request->filled('password'),
            'ip' => $request->ip(),
        ]);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            Log::warning('MOBILE LOGIN FAILED', [
                'request_id' => $requestId,
                'status_code' => 422,
                'error_type' => 'validation',
                'fields' => array_keys($validator->errors()->toArray()),
            ]);

            return $this->validationErrorResponse($validator->errors());
        }

        $identifier = trim((string) $request->input('email'));
        $type = $this->isEmail($identifier) ? 'email' : 'username';
        if ($type === 'email') {
            $identifier = Str::lower($identifier);
        }

        Log::info('MOBILE LOGIN IDENTIFIER RESOLVED', [
            'request_id' => $requestId,
            'identifier_type' => $type,
        ]);

        try {
            $this->ensureIsNotRateLimited($type, $request, $identifier);
        } catch (ValidationException $exception) {
            $seconds = RateLimiter::availableIn($this->throttleKey($identifier, $request));
            Log::warning('MOBILE LOGIN FAILED', [
                'request_id' => $requestId,
                'status_code' => 429,
                'error_type' => 'rate_limited',
                'retry_after_seconds' => $seconds,
            ]);

            return $this->errorResponse(
                Arr::join(Arr::flatten($exception->errors()), ', ', ' and '),
                429,
                ['retry_after_seconds' => $seconds],
                $exception->errors(),
                'RATE_LIMITED'
            );
        }

        $user = User::query()
            ->where($type, $identifier)
            ->where('user_type', 'buyer')
            ->first();

        if (! $user || ! Hash::check((string) $request->password, (string) $user->password)) {
            RateLimiter::hit($this->throttleKey($identifier, $request));

            Log::warning('MOBILE LOGIN FAILED', [
                'request_id' => $requestId,
                'status_code' => 401,
                'error_type' => 'invalid_credentials',
                'identifier_type' => $type,
            ]);

            return $this->errorResponse(
                __('auth.failed'),
                401,
                null,
                ['credentials' => [__('auth.failed')]],
                'INVALID_CREDENTIALS'
            );
        }

        if ((int) $user->status === 0) {
            Log::warning('MOBILE LOGIN FAILED', [
                'request_id' => $requestId,
                'status_code' => 403,
                'error_type' => 'account_disabled',
                'user_id' => $user->id,
            ]);
            return $this->errorResponse(__('Your account is disabled. Please contact support.'), 403);
        }

        if ((int) $user->status === 2) {
            Log::warning('MOBILE LOGIN FAILED', [
                'request_id' => $requestId,
                'status_code' => 403,
                'error_type' => 'account_closed',
                'user_id' => $user->id,
            ]);
            return $this->errorResponse(__('Your account is closed. Please contact support if you want to restore it.'), 403);
        }

        RateLimiter::clear($this->throttleKey($identifier, $request));

        try {
            $token = $user->createToken('auth_token')->plainTextToken;
        } catch (\Throwable $exception) {
            Log::error('MOBILE LOGIN ERROR', [
                'request_id' => $requestId,
                'status_code' => 500,
                'error_type' => get_class($exception),
                'user_id' => $user->id,
            ]);

            return $this->errorResponse(
                __('Unable to complete sign in. Please try again.'),
                500
            );
        }

        Log::info('MOBILE LOGIN TOKEN ISSUED', [
            'request_id' => $requestId,
            'user_id' => $user->id,
        ]);

        // Login telemetry must never turn valid credentials/token issuance
        // into a failed sign-in response.
        try {
            LoginActivities::add($user->id);
        } catch (\Throwable $exception) {
            Log::warning('MOBILE LOGIN ACTIVITY WRITE FAILED', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'exception_type' => get_class($exception),
            ]);
        }

        Log::info('MOBILE LOGIN SUCCESS', [
            'request_id' => $requestId,
            'status_code' => 200,
            'user_id' => $user->id,
        ]);

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
