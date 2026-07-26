<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFaCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! setting('fa_verification', 'permission') || ! $request->user()->two_fa) {
            return $next($request);
        }

        $requestsForAuthenticatate = $request;
        $requestsForAuthenticatate['one_time_password'] = is_array($requestsForAuthenticatate['one_time_password']) ? collect($request->get('one_time_password'))->implode('') : $request->get('one_time_password');
        $authenticator = app(Authenticator::class)->boot($requestsForAuthenticatate);

        if ($authenticator->isAuthenticated()) {
            return $next($request);
        }

        if (filled($request->get('one_time_password'))) {
            session()->flash('error', __('Invalid OTP'));
        }

        return $authenticator->makeRequestOneTimePasswordResponse();
    }
}
