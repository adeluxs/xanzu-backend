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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        /*
         * Unique ID for this particular login request.
         *
         * This makes it much easier to trace one mobile request
         * from beginning to end inside laravel.log.
         */
        $requestId = (string) Str::uuid();

        Log::info('MOBILE LOGIN REQUEST RECEIVED', [
            'request_id' => $requestId,
            'route' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),

            // Safe fields only.
            // DO NOT log password.
            'email_or_username' => $request->input('email'),

            'has_password' => $request->filled('password'),

            'headers' => [
                'accept' => $request->header('Accept'),
                'content_type' => $request->header('Content-Type'),
            ],
        ]);

        try {

            /*
             |--------------------------------------------------------------------------
             | Validation
             |--------------------------------------------------------------------------
             */

            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
            ]);

            if ($validator->fails()) {

                $message = $validator->errors()->first();

                Log::warning('MOBILE LOGIN VALIDATION FAILED', [
                    'request_id' => $requestId,
                    'errors' => $validator->errors()->toArray(),
                ]);

                return $this->validationErrorResponse($message);
            }

            /*
             |--------------------------------------------------------------------------
             | Determine identifier type
             |--------------------------------------------------------------------------
             */

            $identifier = trim((string) $request->input('email'));

            $type = $this->isEmail($identifier)
                ? 'email'
                : 'username';

            if ($type === 'email') {
                $identifier = Str::lower($identifier);
            }

            Log::info('MOBILE LOGIN IDENTIFIER RESOLVED', [
                'request_id' => $requestId,
                'identifier_type' => $type,
                'identifier' => $identifier,
            ]);

            /*
             |--------------------------------------------------------------------------
             | Rate limiting
             |--------------------------------------------------------------------------
             */

            try {

                $this->ensureIsNotRateLimited(
                    $type,
                    $request,
                    $identifier
                );

            } catch (ValidationException $exception) {

                Log::warning('MOBILE LOGIN RATE LIMITED', [
                    'request_id' => $requestId,
                    'identifier' => $identifier,
                    'ip' => $request->ip(),
                    'errors' => $exception->errors(),
                ]);

                return $this->validationErrorResponse(
                    $exception->errors()
                );
            }

            /*
             |--------------------------------------------------------------------------
             | Find buyer
             |--------------------------------------------------------------------------
             */

            Log::info('MOBILE LOGIN SEARCHING FOR USER', [
                'request_id' => $requestId,
                'identifier_type' => $type,
                'identifier' => $identifier,
                'required_user_type' => 'buyer',
            ]);

            $user = User::query()
                ->where($type, $identifier)
                ->where('user_type', 'buyer')
                ->first();

            if (! $user) {

                Log::warning('MOBILE LOGIN USER NOT FOUND', [
                    'request_id' => $requestId,
                    'identifier_type' => $type,
                    'identifier' => $identifier,
                ]);

                RateLimiter::hit(
                    $this->throttleKey($identifier, $request)
                );

                return $this->validationErrorResponse(
                    __('auth.failed')
                );
            }

            Log::info('MOBILE LOGIN USER FOUND', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'status' => $user->status,
            ]);

            /*
             |--------------------------------------------------------------------------
             | Check password
             |--------------------------------------------------------------------------
             */

            if (! Hash::check(
                (string) $request->password,
                (string) $user->password
            )) {

                Log::warning('MOBILE LOGIN PASSWORD FAILED', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'identifier' => $identifier,
                    'ip' => $request->ip(),
                ]);

                RateLimiter::hit(
                    $this->throttleKey($identifier, $request)
                );

                return $this->validationErrorResponse(
                    __('auth.failed')
                );
            }

            Log::info('MOBILE LOGIN PASSWORD VERIFIED', [
                'request_id' => $requestId,
                'user_id' => $user->id,
            ]);

            /*
             |--------------------------------------------------------------------------
             | Account status
             |--------------------------------------------------------------------------
             */

            if ((int) $user->status === 0) {

                Log::warning('MOBILE LOGIN ACCOUNT DISABLED', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'status' => $user->status,
                ]);

                return $this->errorResponse(
                    __('Your account is disabled. Please contact support.'),
                    403
                );
            }

            if ((int) $user->status === 2) {

                Log::warning('MOBILE LOGIN ACCOUNT CLOSED', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'status' => $user->status,
                ]);

                return $this->errorResponse(
                    __('Your account is closed. Please contact support if you want to restore it.'),
                    403
                );
            }

            /*
             |--------------------------------------------------------------------------
             | Clear rate limiter
             |--------------------------------------------------------------------------
             */

            RateLimiter::clear(
                $this->throttleKey($identifier, $request)
            );

            /*
             |--------------------------------------------------------------------------
             | Create Sanctum token
             |--------------------------------------------------------------------------
             */

            Log::info('MOBILE LOGIN CREATING TOKEN', [
                'request_id' => $requestId,
                'user_id' => $user->id,
            ]);

            $token = $user
                ->createToken('auth_token')
                ->plainTextToken;

            /*
             * Never log:
             *
             * Log::info($token);
             *
             * because anyone reading laravel.log would be able
             * to authenticate as this user.
             */

            /*
             |--------------------------------------------------------------------------
             | Record login activity
             |--------------------------------------------------------------------------
             */

            Log::info('MOBILE LOGIN RECORDING ACTIVITY', [
                'request_id' => $requestId,
                'user_id' => $user->id,
            ]);

            LoginActivities::add($user->id);

            /*
             |--------------------------------------------------------------------------
             | Build mobile response
             |--------------------------------------------------------------------------
             */

            $payload = MobileAuthPayload::make(
                $user,
                $token
            );

            /*
             * Create a safe version for logging.
             *
             * The actual token is intentionally hidden.
             */

            $safePayload = $payload;

            if (isset($safePayload['token'])) {
                $safePayload['token'] = '[HIDDEN]';
            }

            Log::info('MOBILE LOGIN RESPONSE SENT', [
                'request_id' => $requestId,
                'status_code' => 200,
                'user_id' => $user->id,
                'response' => $safePayload,
            ]);

            return $this->successResponse($payload);

        } catch (Throwable $exception) {

            /*
             |--------------------------------------------------------------------------
             | Unexpected exception
             |--------------------------------------------------------------------------
             */

            Log::error('MOBILE LOGIN UNEXPECTED ERROR', [
                'request_id' => $requestId,

                'message' => $exception->getMessage(),

                'exception' => get_class($exception),

                'file' => $exception->getFile(),

                'line' => $exception->getLine(),

                'identifier' => $request->input('email'),

                'ip' => $request->ip(),

                /*
                 * Usually enough for debugging.
                 * Remove in production if logs become too large.
                 */
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Login failed because of an internal server error.',
                500
            );
        }
    }


    public function logout(Request $request)
    {
        $requestId = (string) Str::uuid();

        Log::info('MOBILE LOGOUT REQUEST RECEIVED', [
            'request_id' => $requestId,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {

            $request->user()
                ?->currentAccessToken()
                ?->delete();

            Log::info('MOBILE LOGOUT SUCCESS', [
                'request_id' => $requestId,
                'user_id' => $request->user()?->id,
            ]);

            return $this->successWithoutDataResponse(
                __('Logged out')
            );

        } catch (Throwable $exception) {

            Log::error('MOBILE LOGOUT ERROR', [
                'request_id' => $requestId,
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return $this->errorResponse(
                'Logout failed.',
                500
            );
        }
    }


    private function isEmail(string $param): bool
    {
        return filter_var(
            $param,
            FILTER_VALIDATE_EMAIL
        ) !== false;
    }


    private function throttleKey(
        string $identifier,
        Request $request
    ): string {

        return Str::transliterate(
            Str::lower($identifier)
            .'|'.
            $request->ip()
        );
    }


    private function ensureIsNotRateLimited(
        string $type,
        Request $request,
        string $identifier
    ): void {

        $throttleKey = $this->throttleKey(
            $identifier,
            $request
        );

        if (! RateLimiter::tooManyAttempts(
            $throttleKey,
            5
        )) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn(
            $throttleKey
        );

        throw ValidationException::withMessages([
            $type => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}<?php

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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        /*
         * Unique ID for this particular login request.
         *
         * This makes it much easier to trace one mobile request
         * from beginning to end inside laravel.log.
         */
        $requestId = (string) Str::uuid();

        Log::info('MOBILE LOGIN REQUEST RECEIVED', [
            'request_id' => $requestId,
            'route' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),

            // Safe fields only.
            // DO NOT log password.
            'email_or_username' => $request->input('email'),

            'has_password' => $request->filled('password'),

            'headers' => [
                'accept' => $request->header('Accept'),
                'content_type' => $request->header('Content-Type'),
            ],
        ]);

        try {

            /*
             |--------------------------------------------------------------------------
             | Validation
             |--------------------------------------------------------------------------
             */

            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
            ]);

            if ($validator->fails()) {

                $message = $validator->errors()->first();

                Log::warning('MOBILE LOGIN VALIDATION FAILED', [
                    'request_id' => $requestId,
                    'errors' => $validator->errors()->toArray(),
                ]);

                return $this->validationErrorResponse($message);
            }

            /*
             |--------------------------------------------------------------------------
             | Determine identifier type
             |--------------------------------------------------------------------------
             */

            $identifier = trim((string) $request->input('email'));

            $type = $this->isEmail($identifier)
                ? 'email'
                : 'username';

            if ($type === 'email') {
                $identifier = Str::lower($identifier);
            }

            Log::info('MOBILE LOGIN IDENTIFIER RESOLVED', [
                'request_id' => $requestId,
                'identifier_type' => $type,
                'identifier' => $identifier,
            ]);

            /*
             |--------------------------------------------------------------------------
             | Rate limiting
             |--------------------------------------------------------------------------
             */

            try {

                $this->ensureIsNotRateLimited(
                    $type,
                    $request,
                    $identifier
                );

            } catch (ValidationException $exception) {

                Log::warning('MOBILE LOGIN RATE LIMITED', [
                    'request_id' => $requestId,
                    'identifier' => $identifier,
                    'ip' => $request->ip(),
                    'errors' => $exception->errors(),
                ]);

                return $this->validationErrorResponse(
                    $exception->errors()
                );
            }

            /*
             |--------------------------------------------------------------------------
             | Find buyer
             |--------------------------------------------------------------------------
             */

            Log::info('MOBILE LOGIN SEARCHING FOR USER', [
                'request_id' => $requestId,
                'identifier_type' => $type,
                'identifier' => $identifier,
                'required_user_type' => 'buyer',
            ]);

            $user = User::query()
                ->where($type, $identifier)
                ->where('user_type', 'buyer')
                ->first();

            if (! $user) {

                Log::warning('MOBILE LOGIN USER NOT FOUND', [
                    'request_id' => $requestId,
                    'identifier_type' => $type,
                    'identifier' => $identifier,
                ]);

                RateLimiter::hit(
                    $this->throttleKey($identifier, $request)
                );

                return $this->validationErrorResponse(
                    __('auth.failed')
                );
            }

            Log::info('MOBILE LOGIN USER FOUND', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'status' => $user->status,
            ]);

            /*
             |--------------------------------------------------------------------------
             | Check password
             |--------------------------------------------------------------------------
             */

            if (! Hash::check(
                (string) $request->password,
                (string) $user->password
            )) {

                Log::warning('MOBILE LOGIN PASSWORD FAILED', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'identifier' => $identifier,
                    'ip' => $request->ip(),
                ]);

                RateLimiter::hit(
                    $this->throttleKey($identifier, $request)
                );

                return $this->validationErrorResponse(
                    __('auth.failed')
                );
            }

            Log::info('MOBILE LOGIN PASSWORD VERIFIED', [
                'request_id' => $requestId,
                'user_id' => $user->id,
            ]);

            /*
             |--------------------------------------------------------------------------
             | Account status
             |--------------------------------------------------------------------------
             */

            if ((int) $user->status === 0) {

                Log::warning('MOBILE LOGIN ACCOUNT DISABLED', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'status' => $user->status,
                ]);

                return $this->errorResponse(
                    __('Your account is disabled. Please contact support.'),
                    403
                );
            }

            if ((int) $user->status === 2) {

                Log::warning('MOBILE LOGIN ACCOUNT CLOSED', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'status' => $user->status,
                ]);

                return $this->errorResponse(
                    __('Your account is closed. Please contact support if you want to restore it.'),
                    403
                );
            }

            /*
             |--------------------------------------------------------------------------
             | Clear rate limiter
             |--------------------------------------------------------------------------
             */

            RateLimiter::clear(
                $this->throttleKey($identifier, $request)
            );

            /*
             |--------------------------------------------------------------------------
             | Create Sanctum token
             |--------------------------------------------------------------------------
             */

            Log::info('MOBILE LOGIN CREATING TOKEN', [
                'request_id' => $requestId,
                'user_id' => $user->id,
            ]);

            $token = $user
                ->createToken('auth_token')
                ->plainTextToken;

            /*
             * Never log:
             *
             * Log::info($token);
             *
             * because anyone reading laravel.log would be able
             * to authenticate as this user.
             */

            /*
             |--------------------------------------------------------------------------
             | Record login activity
             |--------------------------------------------------------------------------
             */

            Log::info('MOBILE LOGIN RECORDING ACTIVITY', [
                'request_id' => $requestId,
                'user_id' => $user->id,
            ]);

            LoginActivities::add($user->id);

            /*
             |--------------------------------------------------------------------------
             | Build mobile response
             |--------------------------------------------------------------------------
             */

            $payload = MobileAuthPayload::make(
                $user,
                $token
            );

            /*
             * Create a safe version for logging.
             *
             * The actual token is intentionally hidden.
             */

            $safePayload = $payload;

            if (isset($safePayload['token'])) {
                $safePayload['token'] = '[HIDDEN]';
            }

            Log::info('MOBILE LOGIN RESPONSE SENT', [
                'request_id' => $requestId,
                'status_code' => 200,
                'user_id' => $user->id,
                'response' => $safePayload,
            ]);

            return $this->successResponse($payload);

        } catch (Throwable $exception) {

            /*
             |--------------------------------------------------------------------------
             | Unexpected exception
             |--------------------------------------------------------------------------
             */

            Log::error('MOBILE LOGIN UNEXPECTED ERROR', [
                'request_id' => $requestId,

                'message' => $exception->getMessage(),

                'exception' => get_class($exception),

                'file' => $exception->getFile(),

                'line' => $exception->getLine(),

                'identifier' => $request->input('email'),

                'ip' => $request->ip(),

                /*
                 * Usually enough for debugging.
                 * Remove in production if logs become too large.
                 */
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Login failed because of an internal server error.',
                500
            );
        }
    }


    public function logout(Request $request)
    {
        $requestId = (string) Str::uuid();

        Log::info('MOBILE LOGOUT REQUEST RECEIVED', [
            'request_id' => $requestId,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {

            $request->user()
                ?->currentAccessToken()
                ?->delete();

            Log::info('MOBILE LOGOUT SUCCESS', [
                'request_id' => $requestId,
                'user_id' => $request->user()?->id,
            ]);

            return $this->successWithoutDataResponse(
                __('Logged out')
            );

        } catch (Throwable $exception) {

            Log::error('MOBILE LOGOUT ERROR', [
                'request_id' => $requestId,
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return $this->errorResponse(
                'Logout failed.',
                500
            );
        }
    }


    private function isEmail(string $param): bool
    {
        return filter_var(
            $param,
            FILTER_VALIDATE_EMAIL
        ) !== false;
    }


    private function throttleKey(
        string $identifier,
        Request $request
    ): string {

        return Str::transliterate(
            Str::lower($identifier)
            .'|'.
            $request->ip()
        );
    }


    private function ensureIsNotRateLimited(
        string $type,
        Request $request,
        string $identifier
    ): void {

        $throttleKey = $this->throttleKey(
            $identifier,
            $request
        );

        if (! RateLimiter::tooManyAttempts(
            $throttleKey,
            5
        )) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn(
            $throttleKey
        );

        throw ValidationException::withMessages([
            $type => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}