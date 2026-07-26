<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\LoginActivities;
use App\Models\PhoneOtp;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    use ApiResponse, NotifyTrait;

    public function store(Request $request)
    {
        $usernameRequired = $this->isFieldRequired('username');
        $referralCodeRequired = $this->isFieldRequired('referral_code');
        $genderRequired = $this->isFieldRequired('gender');

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'username' => [Rule::requiredIf($usernameRequired), 'string', 'alpha_num', 'max:255', 'unique:users,username'],
            'otp_id' => 'required|exists:phone_otps,id',
            'invite' => [Rule::requiredIf($referralCodeRequired), 'nullable', 'exists:users,referral_code'],
            'gender' => [Rule::requiredIf($genderRequired), 'nullable', 'in:male,female,other', 'max:255'],
            'i_agree' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $referralUser = null;
            if (getPageSetting('referral_code_show') && $request->filled('invite')) {
                $referralUser = User::where('referral_code', $request->invite)->first();
            }

            $username = !$request->filled('username')
                ? generateUniqueUsername(trim($request->first_name . ' ' . $request->last_name))
                : $request->username;

            $phoneOtp = PhoneOtp::with('country')->whereKey($request->otp_id)->where('is_verified', true)->first();

            if (!$phoneOtp) {
                return $this->errorResponse('Invalid OTP verification.');
            }

            $country = $phoneOtp->country ?? null;
            if (!$country) {
                $location = getLocation();
                $countryName = $location->name;
            } else {
                $countryName = $country->name;
            }
            $phone = formatPhoneNumber($phoneOtp->phone, $phoneOtp->dial_code, true, false);

            // Create user account
            DB::beginTransaction();

            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'country' => $countryName,
                'phone' => $phone,
                'phone_verified_at' => $phoneOtp->updated_at,
            ];

            if (getPageSetting('gender_show')) {
                $userData['gender'] = $request->gender;
            }

            if ($referralUser) {
                $userData['ref_id'] = $referralUser->id;
            }

            $user = User::create($userData);

            $this->distributeSignUpBonus($user);

            if ($referralUser) {
                $this->processReferralBonus($referralUser, $user);
            }

            LoginActivities::add($user->id);

            DB::commit();

            // delete otp record
            $phoneOtp->delete();

            return $this->successResponse([
                'token' => $user->createToken('auth_token')->plainTextToken,
            ], 'Registration successful!');
        } catch (\Throwable $throwable) {
            \Log::error('Registration Error: ' . $throwable->getMessage());
            DB::rollBack();

            return $this->errorResponse('Sorry! Something went wrong.');
        }
    }

    private function isFieldRequired($field)
    {
        return getPageSetting("{$field}_show") && getPageSetting("{$field}_validation");
    }

    public function processReferralBonus($referral, $user)
    {
        $email_verification = (setting('email_verification', 'permission') && $user->email_verified_at !== null) || !setting('email_verification', 'permission');

        DB::beginTransaction();
        try {
            if (setting('sign_up_referral', 'permission') && $email_verification) {

                $referralBonus = (float) setting('referral_bonus', 'fee');
                $provider = $referral;

                Transaction::create([
                    'user_id' => $provider->id,
                    'from_user_id' => $user->id,
                    'from_model' => 'User',
                    'wallet_type' => 'default',
                    'description' => 'Referral Bonus via ' . $user->full_name,
                    'type' => TxnType::Referral,
                    'amount' => $referralBonus,
                    'charge' => 0,
                    'final_amount' => $referralBonus,
                    'method' => 'System',
                    'status' => TxnStatus::Success,
                ]);

                $provider->increment('balance', $referralBonus);

                $shortcodes = [
                    '[[full_name]]' => $provider->full_name,
                    '[[referred_name]]' => $user->full_name,
                    '[[referred_account_no]]' => $user->account_number,
                    '[[joined_at]]' => $user->created_at,
                    '[[referral_link]]' => frontendPanelUrl('referral', null, false),
                    '[[site_title]]' => setting('site_title', 'global'),
                ];

                $this->sendNotify($provider->email, 'user_referral_join', 'User', $shortcodes, $provider->phone, $provider->id, frontendPanelUrl('referral', null, false));
            }
        } catch (\Throwable $th) {
            DB::rollBack();
        }
        DB::commit();
    }

    private function distributeSignUpBonus($user)
    {
        if (setting('referral_signup_bonus', 'permission') && (float) setting('signup_bonus', 'fee') > 0) {
            $signupBonus = (float) setting('signup_bonus', 'fee');
            $user->increment('balance', $signupBonus);
            (new Txn)->new($signupBonus, 0, $signupBonus, 'system', 'Signup Bonus', TxnType::SignupBonus, TxnStatus::Success, null, null, $user->id);
        }
    }
}
