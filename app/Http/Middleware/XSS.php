<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mews\Purifier\Facades\Purifier;

class XSS
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):((Response|RedirectResponse))  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $userInput = $request->all();
        array_walk_recursive($userInput, function (&$value) {
            // Preserve null and non-string payloads exactly as-is.
            if ($value === null || !is_string($value)) {
                return;
            }

            if (strip_tags($value) !== $value) {
                $value = Purifier::clean($value);
            }
        });
        $request->merge($userInput);

        return $next($request);
    }
}
