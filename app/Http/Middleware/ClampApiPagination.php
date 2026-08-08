<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent mobile/API clients from accidentally requesting huge result sets.
 * Controllers can still choose a lower page size; this only enforces a safe
 * global ceiling and normalizes invalid values before queries are built.
 */
class ClampApiPagination
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query->has('per_page') || $request->request->has('per_page')) {
            $requested = (int) $request->input('per_page', 15);
            $request->merge(['per_page' => max(1, min($requested, 50))]);
        }

        if ($request->query->has('page') || $request->request->has('page')) {
            $request->merge(['page' => max(1, (int) $request->input('page', 1))]);
        }

        return $next($request);
    }
}
