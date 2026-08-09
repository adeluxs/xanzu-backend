<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\LoginActivities;
use App\Models\PhoneOtp;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Auth\MobileAuthPayload;
use App\Traits\ApiResponse;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    use ApiResponse, NotifyTrait;

    public function store(Request $request)
    {
        $otpEnabled = (bool) setting('otp_verification', 'permission');
        $firstNameRequired = $this->isFieldRequired('first_name');
        $lastNameRequired = $this->isFieldRequired('last_name');
        $usernameRequired = $this->isFieldRequired('username');
        $referralCodeRequired = $this->isFieldRequired('referral_code');
        $genderRequired = $this->isFieldRequired('gender');

        $validator = Validator::make($request->all(), [
            'first_name' => [Rule::requiredIf($firstNameRequired), 'nullable', 'string', 'max:255'],
            'last_name' => [Rule::requiredIf($lastNameRequired), 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'username' => [Rule::requiredIf($usernameRequired), 'nullable', 'string', 'alpha_num', 'max:255', 'unique:users,username'],
            'otp_id' => [Rule::requiredIf($otpEnabled), 'nullable', 'integer', 'exists:phone_otps,id'],
            // When phone OTP is disabled the mobile app still collects the
            // destination phone/dial code; when it is enabled the verified OTP
            // record is the authority and these request fields are optional.
            'phone' => [Rule::requiredIf(! $otpEnabled), 'nullable', 'string', 'max:30'],
            'dial_code' => [Rule::requiredIf(! $otpEnabled), 'nullable', 'exists:countries,dial_code'],
            'invite' => [Rule::requiredIf($referralCodeRequired), 'nullable', 'exists:users,referral_code'],
            'gender' => [Rule::requiredIf($genderRequired), 'nullable', 'in:male,female,other', 'max:255'],
            'i_agree' => ['required', 'accepted'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $result = DB::transaction(function () use ($request, $otpEnabled) {
                $referralUser = null;
                if (getPageSetting('referral_code_show') && $request->filled('invite')) {
                    $referralUser = User::where('referral_code', $request->invite)->first();
                }

                $username = ! $request->filled('username')
                    ? generateUniqueUsername(trim($request->first_name.' '.$request->last_name))
                    : trim((string) $request->username);

                $phoneOtp = null;
                if ($otpEnabled) {
                    $phoneOtp = PhoneOtp::with('country')
                        ->whereKey($request->otp_id)
                        ->where('is_verified', true)
                        ->where('expires_at', '>', now())
                        ->lockForUpdate()
                        ->first();

                    if (! $phoneOtp) {
                        return ['error' => 'Invalid or expired OTP verification.'];
                    }
                }

                $dialCode = $phoneOtp?->dial_code ?: trim((string) $request->dial_code);
                $rawPhone = $phoneOtp?->phone ?: trim((string) $request->phone);
                $phone = formatPhoneNumber($rawPhone, $dialCode, true, false);

                if ($phone === '' || $phone === '+') {
                    return ['error' => 'A valid phone number is required.'];
                }

                if (User::where('phone', $phone)->lockForUpdate()->first(['id'])) {
                    return ['error' => 'Phone number already registered.'];
                }

                $country = $phoneOtp?->country;
                if (! $country && $dialCode !== '') {
                    $country = Country::where('dial_code', $dialCode)->first();
                }
                $countryName = $country?->name ?: (string) (getLocation()->name ?? '');

                $firstName = trim((string) $request->first_name);
                $lastName = trim((string) $request->last_name);
                if ($firstName === '') {
                    $firstName = Str::title(str_replace(['.', '_', '-'], ' ', Str::before((string) $request->email, '@')));
                }

                $userData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $username,
                    'email' => Str::lower(trim((string) $request->email)),
                    'password' => Hash::make((string) $request->password),
                    'user_type' => 'buyer',
                    'transfer_status' => (bool) setting('transfer_default_buyer', 'transfer', true),
                    'country' => $countryName,
                    'phone' => $phone,
                    'phone_verified_at' => $phoneOtp ? now() : null,
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

                if ($phoneOtp) {
                    $phoneOtp->delete();
                }

                $token = $user->createToken('auth_token')->plainTextToken;

                return ['user' => $user, 'token' => $token];
            }, 3);

            if (isset($result['error'])) {
                return $this->validationErrorResponse($result['error']);
            }

            /** @var User $user */
            $user = $result['user'];

            return $this->successResponse(
                MobileAuthPayload::make($user, $result['token']),
                'Registration successful!'
            );
        } catch (\Throwable $throwable) {
            Log::error('Registration Error', [
                'message' => $throwable->getMessage(),
                'exception' => get_class($throwable),
                'email' => $request->input('email'),
            ]);

            return $this->errorResponse('Sorry! Something went wrong while creating your account. Please try again.', 500);
        }
    }

    private function isFieldRequired(string $field): bool
    {
        return (bool) getPageSetting("{$field}_show") && (bool) getPageSetting("{$field}_validation");
    }

    public function processReferralBonus(User $referral, User $user): void
    {
        $emailVerificationSatisfied = ! setting('email_verification', 'permission') || $user->email_verified_at !== null;

        if (! setting('sign_up_referral', 'permission') || ! $emailVerificationSatisfied) {
            return;
        }

        try {
            $referralBonus = (float) setting('referral_bonus', 'fee');
            if ($referralBonus <= 0) {
                return;
            }

            Transaction::create([
                'user_id' => $referral->id,
                'from_user_id' => $user->id,
                'from_model' => 'User',
                'wallet_type' => 'default',
                'description' => 'Referral Bonus via '.$user->full_name,
                'type' => TxnType::Referral,
                'amount' => $referralBonus,
                'charge' => 0,
                'final_amount' => $referralBonus,
                'method' => 'System',
                'status' => TxnStatus::Success,
            ]);

            $referral->increment('balance', $referralBonus);

            $shortcodes = [
                '[[full_name]]' => $referral->full_name,
                '[[referred_name]]' => $user->full_name,
                '[[referred_account_no]]' => $user->account_number,
                '[[joined_at]]' => $user->created_at,
                '[[referral_link]]' => frontendPanelUrl('referral', null, false),
                '[[site_title]]' => setting('site_title', 'global'),
            ];

            $this->sendNotify(
                $referral->email,
                'user_referral_join',
                'User',
                $shortcodes,
                $referral->phone,
                $referral->id,
                frontendPanelUrl('referral', null, false)
            );
        } catch (\Throwable $throwable) {
            // Referral notification/bonus issues must not make a valid buyer
            // registration unusable. The outer registration transaction keeps
            // database writes atomic; this log preserves the failure evidence.
            Log::warning('Referral signup processing failed', [
                'referral_user_id' => $referral->id,
                'new_user_id' => $user->id,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    private function distributeSignUpBonus(User $user): void
    {
        if (setting('referral_signup_bonus', 'permission') && (float) setting('signup_bonus', 'fee') > 0) {
            $signupBonus = (float) setting('signup_bonus', 'fee');
            $user->increment('balance', $signupBonus);
            (new Txn)->new($signupBonus, 0, $signupBonus, 'system', 'Signup Bonus', TxnType::SignupBonus, TxnStatus::Success, null, null, $user->id);
        }
    }
}
