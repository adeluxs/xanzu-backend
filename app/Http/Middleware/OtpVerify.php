<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OtpVerify
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):((Response|RedirectResponse))  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (setting('otp_verification', 'permission') && $request->user()->phone_verified == 1) {
            return $next($request);
        }

        return to_route('otp.verify')->with('success', __('Otp Send Successfully'));
    }
}
