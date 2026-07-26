<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DemoMode
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):((Response|RedirectResponse))  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        if (! env('APP_DEMO', false)) {

            return $next($request);

        } elseif ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE') || $request->route()->getName() == 'admin.user.login' || $request->route()->getName() == 'admin.theme.status-update') {

            notify()->warning(__('You cannot change anything in this demo version'), 'warning');

            return back();
        }

        return $next($request);
    }
}
