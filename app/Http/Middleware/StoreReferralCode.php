<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StoreReferralCode
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):((Response|RedirectResponse))  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->cookie('invite') == null && $request->has('invite')) {
            $referral = User::where('referral_code', $request->get('invite'))->first();

            if (! $referral) {
                return $response;
            }
            $response->withCookie(cookie('invite', $referral->id, $referral->lifetime_minutes));

            return $response;
        }

        return $response;

    }
}
