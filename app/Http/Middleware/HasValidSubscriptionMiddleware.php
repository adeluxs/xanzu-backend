<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasValidSubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (isPlanModuleEnabled() && auth()->user()->hasValidSubscription === false) {
            notify()->error(__('You need to subscribe to a plan to access this functionality!'));

            return redirect('seller-subscription');
        }

        return $next($request);
    }
}
