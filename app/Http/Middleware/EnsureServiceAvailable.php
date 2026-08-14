<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureServiceAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting_enabled('service_suspended', 'service_availability', false)) {
            return $next($request);
        }

        if ($this->isSystemRecoveryRequest($request)) {
            return $next($request);
        }

        $requestId = (string) ($request->attributes->get('request_id') ?: Str::uuid());
        $request->attributes->set('request_id', $requestId);
        $message = trim((string) setting(
            'service_suspension_message',
            'service_availability',
            'Payment has not been made. Please contact the service provider to restore access.'
        ));

        Log::notice('SERVICE_SUSPENSION_REQUEST_BLOCKED', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
        ]);

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => $message,
                'code' => 'SERVICE_SUSPENDED',
                'data' => [
                    'service_suspended' => true,
                    'service_suspension_message' => $message,
                ],
                'errors' => null,
                'status_code' => 503,
                'request_id' => $requestId,
            ], 503)->withHeaders([
                'Cache-Control' => 'no-store, private',
                'Retry-After' => '300',
            ]);
        }

        return response()
            ->view('errors.service-suspended', ['suspensionMessage' => $message], 503)
            ->withHeaders([
                'Cache-Control' => 'no-store, private',
                'Retry-After' => '300',
            ]);
    }

    /**
     * Keep only non-interactive recovery/status endpoints available.
     *
     * Administrator routes are deliberately not exempt. Once suspension is
     * enabled, the administrator HTTP panel is locked with the rest of the
     * application and access can be restored only from the server console.
     */
    private function isSystemRecoveryRequest(Request $request): bool
    {
        return $request->is(
            'api/get-settings',
            'up',
            'ipn/*',
            'status/*',
            'site-cron',
            'notification-tune',
        );
    }
}
