<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = trim((string) $request->header('X-Request-ID'));
        $requestId = preg_match('/^[A-Za-z0-9._:-]{8,100}$/', $incoming)
            ? $incoming
            : (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);

        $authAction = $this->authAction($request);
        $startedAt = hrtime(true);

        if ($authAction !== null) {
            Log::info('MOBILE AUTH HTTP REQUEST', [
                'request_id' => $requestId,
                'auth_action' => $authAction,
                'method' => $request->method(),
                'path' => $request->path(),
                'mobile_client' => $request->header('X-Mobile-Client'),
                'accepts_json' => $request->expectsJson(),
                'has_identifier' => $request->filled('email'),
                'has_password' => $request->filled('password'),
                'has_phone' => $request->filled('phone'),
                'has_otp' => $request->filled('otp'),
                'has_otp_id' => $request->filled('otp_id'),
                'ip' => $request->ip(),
            ]);
        }

        try {
            $response = $next($request);
        } catch (Throwable $throwable) {
            if ($authAction !== null) {
                Log::error('MOBILE AUTH HTTP EXCEPTION', [
                    'request_id' => $requestId,
                    'auth_action' => $authAction,
                    'exception_type' => get_class($throwable),
                    'duration_ms' => $this->durationMs($startedAt),
                ]);
            }
            throw $throwable;
        }

        $response->headers->set('X-Request-ID', $requestId);

        if ($authAction !== null) {
            Log::info('MOBILE AUTH HTTP RESPONSE', [
                'request_id' => $requestId,
                'auth_action' => $authAction,
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $this->durationMs($startedAt),
            ]);
        }

        return $response;
    }

    private function authAction(Request $request): ?string
    {
        $path = '/'.trim($request->path(), '/');
        $actions = [
            '/api/login' => 'login',
            '/api/logout' => 'logout',
            '/api/register' => 'register',
            '/api/register/otp/send' => 'register_otp_send',
            '/api/register/otp/verify' => 'register_otp_verify',
            '/api/forgot-password' => 'forgot_password',
            '/api/reset-verify-otp' => 'reset_otp_verify',
            '/api/reset-password' => 'reset_password',
            '/api/email/verify' => 'email_verify',
            '/api/email/verify/email-send' => 'email_verify_send',
            '/api/2fa/verify' => 'two_factor_verify',
        ];

        foreach ($actions as $suffix => $action) {
            if (Str::endsWith($path, $suffix)) {
                return $action;
            }
        }

        return null;
    }

    private function durationMs(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
