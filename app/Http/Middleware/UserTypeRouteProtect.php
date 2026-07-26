<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserTypeRouteProtect
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        $isSellerRoute = str($request->route()->getPrefix())->contains('seller');
        if ($isSellerRoute && ! $user->is_seller) {
            return redirect(str($request->getUri())->replace('seller', 'user'));
        } elseif (! $isSellerRoute && $user->is_seller) {
            return redirect(str($request->getUri())->replace('user', 'seller'));
        }

        if (! $user->is_seller && $request->route()->getName() == 'user.deposit.index') {
            return to_buyerSellerRoute('topup.index');
        } elseif ($user->is_seller && $request->route()->getName() == 'seller.topup.index') {
            return to_buyerSellerRoute('deposit.index');
        }

        return $next($request);
    }
}
