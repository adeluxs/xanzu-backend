<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureAccess
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissionKey, string $kycKey): Response
    {
        // The route owns the feature/KYC policy. Never let a client select a
        // different settings key through a request parameter.
        if (!setting($permissionKey, 'permission')) {
            notify()->error(__('This feature is currently unavailable'));
            return $request->expectsJson() ? $this->errorResponse(__('This feature is currently unavailable')) : $this->redirectTo();
        } elseif (!setting($kycKey, 'kyc') && !$request->user()->kyc) {
            notify()->error(__('Please verify your KYC.'));
            return $request->expectsJson() ? $this->errorResponse(__('Please verify your KYC.')) : $this->redirectTo();
        }

        return $next($request);
    }

    private function redirectTo()
    {
        return redirect()->away(frontendPanelUrl('setting.index', ['type' => 'kyc'], false));
    }
}
