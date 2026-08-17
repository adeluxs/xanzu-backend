<?php

use App\Console\Commands\LinkPublicAssetsCommand;
use App\Console\Commands\ServiceAccessCommand;
use App\Http\Middleware\CheckDeactivate;
use App\Http\Middleware\ApiRequestId;
use App\Http\Middleware\ClampApiPagination;
use App\Http\Middleware\CheckFeatureAccess;
use App\Http\Middleware\DemoMode;
use App\Http\Middleware\EnsureServiceAvailable;
use App\Http\Middleware\Localization;
use App\Http\Middleware\OtpVerify;
use App\Http\Middleware\TwoFaCheck;
use App\Http\Middleware\UserPermissionChecker;
use App\Http\Middleware\XSS;
use App\Services\ApiResponseService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withCommands([
        ServiceAccessCommand::class,
        LinkPublicAssetsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Keep every API list endpoint bounded, even when a client sends an
        // excessive per_page value. This protects DB, serializer and mobile memory.
        $middleware->appendToGroup('api', ApiRequestId::class);
        $middleware->appendToGroup('api', ClampApiPagination::class);
        $middleware->appendToGroup('api', EnsureServiceAvailable::class);
        $middleware->appendToGroup('web', EnsureServiceAvailable::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'XSS' => XSS::class,
            '2fa' => TwoFaCheck::class,
            'isActive' => CheckDeactivate::class,
            'translate' => Localization::class,
            'isDemo' => DemoMode::class,
            'otp' => OtpVerify::class,
            'userPermissionChecker' => UserPermissionChecker::class,
            'check_feature' => CheckFeatureAccess::class,

        ]);

        $middleware->redirectGuestsTo(function ($guard) {
            if ($guard === 'admin' || request()->is(setting('site_admin_prefix', 'global') . '*')) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function ($guard) {
            if ($guard === 'admin' || request()->is(setting('site_admin_prefix', 'global') . '*')) {
                return route('admin.dashboard');
            }

            return route('home');
        });

        $middleware->validateCsrfTokens([
            'ipn/*',
            'test',
            'bnpl/auth',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (QueryException $e): void {
            $request = request();

            Log::error('DATABASE_QUERY_ERROR', array_filter([
                'request_id' => $request->attributes->get('request_id'),
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->uri(),
                'sqlstate' => $e->errorInfo[0] ?? null,
                'driver_code' => $e->errorInfo[1] ?? null,
                // Deliberately omit bindings: they may contain sensitive data.
                'sql' => Str::limit($e->getSql(), 2000),
            ], static fn ($value) => $value !== null && $value !== ''));
        });

        $exceptions->renderable(function (Throwable $e) {
            $request = request();
            $isApiRequest = $request->is('api/*') || $request->expectsJson();

            if (!$isApiRequest) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return app(ApiResponseService::class)->validationErrorResponse($e->validator->errors());
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => 'Unauthenticated.',
                    'code' => 'UNAUTHORIZED',
                    'data' => null,
                    'errors' => null,
                    'status_code' => 401,
                    'request_id' => $request->attributes->get('request_id'),
                ], 401);
            }

            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            $message = match (true) {
                $status === 403 && trim($e->getMessage()) !== '' => $e->getMessage(),
                $status === 404 => 'Resource not found.',
                $status === 405 => 'Method not allowed.',
                $status === 429 => 'Too many requests. Please try again shortly.',
                $status >= 500 => 'Something went wrong while processing your request. Please try again.',
                trim($e->getMessage()) !== '' => $e->getMessage(),
                default => 'Request failed.',
            };

            $code = match ($status) {
                400 => 'BAD_REQUEST',
                401 => 'UNAUTHORIZED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                405 => 'METHOD_NOT_ALLOWED',
                409 => 'CONFLICT',
                422 => 'VALIDATION_FAILED',
                429 => 'RATE_LIMITED',
                default => $status >= 500 ? 'SERVER_ERROR' : 'REQUEST_FAILED',
            };

            return response()->json([
                'success' => false,
                'status' => false,
                'message' => $message,
                'code' => $code,
                'data' => null,
                'errors' => null,
                'status_code' => $status,
                'request_id' => $request->attributes->get('request_id'),
            ], $status);
        });
    })
    ->create();
