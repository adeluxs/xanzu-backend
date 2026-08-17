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
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
        $exceptions->renderable(function (Throwable $e) {
            $request = request();
            if ($request->expectsJson()) {
                if ($e instanceof ValidationException) {
                    return app(ApiResponseService::class)->validationErrorResponse($e->validator->errors());
                } elseif // unauthorized
                ($e instanceof \Illuminate\Auth\AuthenticationException) {
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
            }
        });
    })
    ->create();
