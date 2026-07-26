<?php

namespace App\Http\Middleware;

use App\Enums\KYCStatus;
use App\Models\Kyc;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerHasKycMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((setting('kyc_verification', 'permission') && in_array($request->user()->kyc, [KYCStatus::NOT_SUBMITTED->value, KYCStatus::Pending->value])) && ! sellerHasKyc()) {

            $__getSellerKyc = getSellerKyc();

            $sellerKycChecker = $request->user()->kycs()->where('kyc_id', getSellerKyc()->id);

            if (! $sellerKycChecker->exists() || in_array($request->user()->kyc, [KYCStatus::NOT_SUBMITTED->value])) {
                notify()->error(__('Please verify your :kyc_name before accessing seller functionality.', ['kyc_name' => $__getSellerKyc->name]), 'Error');

                return to_buyerSellerRoute('kyc.submission', encrypt($__getSellerKyc->id));
            }

            $sellerKycCheck = $sellerKycChecker->clone()->where('status', 'pending')->exists();
            if ($sellerKycCheck) {
                notify()->error(__('Your Seller KYC (:name) is pending. Please wait for approval.', ['name' => $__getSellerKyc->name]), 'Error');

                return to_buyerSellerRoute('dashboard');
            }

            notify()->error(__('Please verify your :kyc_name before accessing seller functionality.', ['kyc_name' => $__getSellerKyc->name]), 'Error');
            $sellerKyc = Kyc::sellerVerification()->first();

            return to_buyerSellerRoute('kyc.submission', encrypt($sellerKyc->id));
        }

        return $next($request);
    }
}
