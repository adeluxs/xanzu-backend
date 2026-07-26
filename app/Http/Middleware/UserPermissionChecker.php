<?php

namespace App\Http\Middleware;

use App\Enums\KYCStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserPermissionChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $settingPermission = null, ?string $userWisePermissions = null): Response
    {
        $user = auth()->user();
        $settingPermission = str($settingPermission)->before(',')->replace(['user_type'], [$user->user_type])->value();
        $userWisePermissions = str($userWisePermissions)->after(',')->replace(['user_type'], [$user->user_type])->value();

        if ($settingPermission == 'kyc_verification' && (setting('kyc_verification', 'permission') && in_array($request->user()->kyc, [KYCStatus::NOT_SUBMITTED->value, KYCStatus::Pending->value]))) {
            notify()->error(__('You do not have permission to access this page.'), 'KYC Verification Permission');

            return back();
        } elseif ($settingPermission != 'kyc_verification' && $settingPermission && ! setting($settingPermission, 'permission')) {
            notify()->error(__('You do not have permission to access this page.'), str($settingPermission)->headline().' Permission');

            return back();
        }

        if ($userWisePermissions && ! empty($userWisePermissions) && ! $user->{$userWisePermissions}) {
            notify()->error(__('You do not have permission to access this page.'), str($userWisePermissions)->headline().' Permission');

            return back();
        }

        return $next($request);
    }
}
