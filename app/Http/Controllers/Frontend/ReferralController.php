<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\LevelReferral;
use App\Models\Setting;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;

class ReferralController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function referral()
    {
        if (! setting('sign_up_referral', 'permission') || ! auth()->user()->referral_status) {
            notify()->error(__('Referral currently unavailble!'), 'Error');

            return to_buyerSellerRoute('dashboard');
        }

        $user = auth()->user();

        // Get or create the first referral link
        $getReferral = $this->referralService->getOrCreateFirstReferralLink($user);

        $level = LevelReferral::max('the_order');

        $rules = json_decode(Setting::where('name', 'referral_rules')->first()?->val);

        $refferedUsers = User::where('ref_id', $user->id)->get();

        return view('frontend::referral.index', compact('getReferral', 'level', 'rules', 'refferedUsers'));
    }

    public function referralTree()
    {
        $level = LevelReferral::max('the_order');

        return view('frontend::referral.tree', compact('level'));
    }

    /**
     * Generate referral links for the authenticated user
     *
     * @return RedirectResponse
     */
    public function generateReferralLinks()
    {
        if (! setting('sign_up_referral', 'permission') || ! auth()->user()->referral_status) {
            notify()->error(__('Referral currently unavailable!'), 'Error');

            return to_buyerSellerRoute('dashboard');
        }

        $user = auth()->user();
        $links = $this->referralService->generateReferralLinks($user);

        if (count($links) > 0) {
            notify()->success(__('Referral links generated successfully!'), 'Success');
        } else {
            notify()->info(__('Referral links already exist.'), 'Info');
        }

        return to_buyerSellerRoute('referral');
    }
}
