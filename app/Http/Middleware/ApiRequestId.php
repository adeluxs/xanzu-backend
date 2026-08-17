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
        $operation = $this->operation($request, $authAction);
        $startedAt = hrtime(true);
        $fileMeta = $this->fileMetadata($request);

        Log::info('API_REQUEST', array_filter([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'route' => $request->route()?->uri(),
            'auth_action' => $authAction,
            'operation' => $operation,
            'mobile_client' => $request->header('X-Mobile-Client'),
            'accepts_json' => $request->expectsJson(),
            'content_type' => $request->header('Content-Type'),
            'input_fields' => $this->safeInputFields($request),
            'files' => $fileMeta,
            'ip' => $request->ip(),
        ], static fn ($value) => $value !== null && $value !== [] && $value !== ''));

        try {
            $response = $next($request);
        } catch (Throwable $throwable) {
            Log::error('API_ERROR', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->uri(),
                'auth_action' => $authAction,
                'operation' => $operation,
                'user_id' => $this->userId($request),
                'exception_type' => get_class($throwable),
                'exception_message' => Str::limit($throwable->getMessage(), 1000),
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            throw $throwable;
        }

        $response->headers->set('X-Request-ID', $requestId);
        $envelope = $this->responseEnvelope($response);
        $httpStatus = $response->getStatusCode();

        $responseLog = array_filter([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'route' => $request->route()?->uri(),
            'auth_action' => $authAction,
            'operation' => $operation,
            'user_id' => $this->userId($request),
            'status_code' => $httpStatus,
            'api_code' => $envelope['code'] ?? null,
            'api_message' => $envelope['message'] ?? null,
            'outcome' => $httpStatus >= 400 || ($envelope['success'] ?? true) === false
                ? 'error'
                : 'success',
            'validation_failed' => $httpStatus === 422 ? true : null,
            'duration_ms' => $this->durationMs($startedAt),
        ], static fn ($value) => $value !== null && $value !== '');

        Log::info('API_RESPONSE', $responseLog);
        if ($httpStatus >= 500) {
            Log::error('API_RESPONSE_ERROR', $responseLog);
        } elseif ($httpStatus >= 400) {
            Log::warning('API_RESPONSE_FAILURE', $responseLog);
        }

        return $response;
    }

    private function safeInputFields(Request $request): array
    {
        $sensitive = [
            'password',
            'password_confirmation',
            'current_password',
            'one_time_password',
            'otp',
            'otp_id',
            'code',
            'token',
            'access_token',
            'secret',
            'secret_key',
            'private_key',
            'api_key',
            'card_number',
            'cardnumber',
            'pan',
            'cvv',
            'cvc',
        ];

        return array_values(array_filter(
            array_keys($request->except($sensitive)),
            static fn ($key) => !in_array(strtolower((string) $key), $sensitive, true)
        ));
    }

    private function fileMetadata(Request $request): array
    {
        $describe = function ($value) use (&$describe) {
            if (is_array($value)) {
                return array_map($describe, $value);
            }

            if (!$value || !method_exists($value, 'getSize')) {
                return null;
            }

            return [
                'size' => $value->getSize(),
                'mime' => $value->getClientMimeType(),
                'valid' => $value->isValid(),
            ];
        };

        return array_map($describe, $request->allFiles());
    }

    private function userId(Request $request): int|string|null
    {
        try {
            return $request->user()?->getAuthIdentifier()
                ?? auth('sanctum')->user()?->getAuthIdentifier();
        } catch (Throwable) {
            return null;
        }
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

    private function operation(Request $request, ?string $authAction): string
    {
        if ($authAction !== null) {
            return $authAction;
        }

        $routeName = trim((string) ($request->route()?->getName() ?? ''));
        if ($routeName !== '') {
            return $routeName;
        }

        return strtolower($request->method()).':'.trim($request->path(), '/');
    }

    private function responseEnvelope(Response $response): array
    {
        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        if (!str_contains($contentType, 'json')) {
            return [];
        }

        $decoded = json_decode((string) $response->getContent(), true);
        if (!is_array($decoded)) {
            return [];
        }

        $success = $decoded['success'] ?? $decoded['status'] ?? null;
        if (is_numeric($success)) {
            $success = ((int) $success) !== 0;
        }

        return array_filter([
            'success' => is_bool($success) ? $success : null,
            'code' => isset($decoded['code']) ? Str::limit((string) $decoded['code'], 120) : null,
            'message' => isset($decoded['message'])
                ? Str::limit(strip_tags((string) $decoded['message']), 300)
                : null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function durationMs(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
