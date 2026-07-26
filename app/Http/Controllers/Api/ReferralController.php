<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferralUserTree;
use App\Models\LevelReferral;
use App\Models\Setting;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $user = auth()->user();

        $setting = Setting::where('name', 'referral_rules')->first();

        $rules = json_decode($setting->val ?? '') ?? [];

        return $this->successResponse([
            'referral_code' => $user->referral_code,
            'rules' => $rules,
            'count_down' => User::where('ref_id', $user->id)->count(),
            'bonus' => setting('sign_up_referral', 'permission') && setting('referral_bonus', 'fee') ? (float) setting('referral_bonus', 'fee').' '.setting('site_currency', 'global') : 0,
        ]);
    }

    public function directReferrals(Request $request)
    {
        $user = auth()->user();
        $referralUsers = $user->referrals()->select('id', 'first_name', 'avatar', 'last_name', 'status', 'email', 'created_at')->paginate($request->integer('per_page', 15));

        $users = $referralUsers->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'avatar' => $user->avatar_path,
                'status' => $user->status,
                'email' => $user->email,
                'referral_profit' => formatCurrency($user->totalReferralProfit()),
                'created_at' => $user->created_at,
            ];
        });

        return $this->successResponse(data: $users, meta: [
            'current_page' => $referralUsers->currentPage(),
            'last_page' => $referralUsers->lastPage(),
            'per_page' => $referralUsers->perPage(),
            'total' => $referralUsers->total(),
        ]);
    }

    public function referralTree()
    {
        $user = auth()->user();
        $maxLevel = LevelReferral::max('the_order');
        $relations = [];
        $path = 'referrals';
        for ($i = 0; $i < ($maxLevel ?: 1); $i++) {
            $relations[] = $path.':id,first_name,last_name,avatar,email,created_at,ref_id,status';
            $path .= '.referrals';
        }
        $user->load($relations);

        return $this->successResponse(ReferralUserTree::make($user));
    }
}
