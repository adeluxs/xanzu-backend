<?php

namespace App\Http\Controllers\Auth;

use App\Enums\KYCStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Events\UserReferred;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\Kyc;
use App\Models\LoginActivities;
use App\Models\ReferralLink;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Support\JsonData;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegisteredUserController extends Controller
{
    use ImageUpload, NotifyTrait;

    public function create(Request $request)
    {
        abort_unless(setting('account_creation', 'permission'), '403', __('User registration is closed now'));

        $page = getPageData('registration');
        $data = JsonData::decodeArray($page?->data);

        $location = getLocation();
        $referralCode = ReferralLink::find($request->cookie('invite'))?->code ?? $request->get('invite');
        $sellerKyc = setting('merchant_register_kyc', 'permission') ? Kyc::whereIn('user_type', ['merchant', 'both'])->first() : null;

        return view('frontend::auth.register', compact('location', 'referralCode', 'data', 'sellerKyc'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request)
    {

        $userType = $request->get('user_type', 'buyer');
        $prefix = $userType === 'merchant' ? 'merchant_' : '';

        $isFirstName = (bool) getPageSetting($prefix . 'first_name_validation') && getPageSetting($prefix . 'first_name_show');
        $isLastName = (bool) getPageSetting($prefix . 'last_name_validation') && getPageSetting($prefix . 'last_name_show');
        $isCountry = (bool) getPageSetting($prefix . 'country_validation') && getPageSetting($prefix . 'country_show');
        $isPhone = (bool) getPageSetting($prefix . 'phone_validation') && getPageSetting($prefix . 'phone_show');
        $isGender = (bool) getPageSetting($prefix . 'gender_validation') && getPageSetting($prefix . 'gender_show');
        $isReferralCode = (bool) getPageSetting($prefix . 'referral_code_validation') && getPageSetting($prefix . 'referral_code_show');
        $request->validate([
            'first_name' => [Rule::requiredIf($isFirstName), 'string', 'max:255'],
            'last_name' => [Rule::requiredIf($isLastName), 'string', 'max:255'],
            'g-recaptcha-response' => [Rule::requiredIf((bool) plugin_active('Google reCaptcha')), new Recaptcha],
            'gender' => [Rule::requiredIf($isGender), 'in:male,female,other'],
            'username' => ['required', 'string', 'max:17', 'unique:users'],
            'country' => [Rule::requiredIf($isCountry), 'string', 'max:255'],
            'phone' => [Rule::requiredIf($isPhone), 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'invite' => [Rule::requiredIf($isReferralCode), 'exists:referral_links,code'],
            'user_type' => ['required', 'in:merchant,buyer'],
            'terms' => ['required', 'accepted'],
        ], [
            'invite.required' => __('Referral code field is required.'),
            'invite.exists' => __('Referral code is invalid'),
        ]);

        $location = getLocation();
        $phoneWithCountryCode = explode(':', $request->get('country', ''));
        $phone = data_get($phoneWithCountryCode, '1', $location->dial_code) . $request->get('phone');
        $country = $isCountry ? explode(':', $request->get('country'))[0] : $location->name;

        $request->merge([
            'country' => $country,
            'phone' => $phone,
        ]);
        DB::beginTransaction();
        try {
            $user = $this->register($request);
        } catch (ValidationException $e) {
            DB::rollBack();
            notify()->error($e->getMessage(), __('Registration failed!'));
            Auth::logout();

            return back()->withInput();
        } catch (Throwable $th) {
            // throw $th;
            notify()->error(__('Something went wrong!'), __('Registration failed!'));
            DB::rollBack();
        }

        DB::commit();

        if (session('checkout')) {
            return to_route('checkout');
        }

        if (isset($user) && !$user->is_seller) {
            return to_route('home');
        }

        return to_route('user.dashboard');
    }

    public function register(Request $request)
    {

        $userType = $request->get('user_type', 'buyer');
        $userData = [
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'gender' => $request->get('gender'),
            'username' => str($request->get('username'))->slug()->value(),
            'country' => $request->country,
            'phone' => $request->phone,
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'user_type' => $userType,
        ];

        if ($request->has('from_social')) {
            // check kyc as per user type if any kyc needed
            if ($userType === 'merchant' && setting('merchant_register_kyc', 'permission') && Kyc::whereIn('user_type', ['merchant', 'both'])->exists()) {
                $kycStatus = KYCStatus::NOT_SUBMITTED;
            } elseif ($userType === 'buyer' && Kyc::whereIn('user_type', ['buyer', 'both'])->exists()) {
                $kycStatus = KYCStatus::NOT_SUBMITTED;
            } else {
                $kycStatus = KYCStatus::Verified;
            }

            $userData['kyc'] = $kycStatus ?? KYCStatus::NOT_SUBMITTED;
        }

        $user = User::create($userData);

        $shortcodes = [
            '[[full_name]]' => $request->get('first_name') . ' ' . $request->get('last_name'),
        ];

        // Notify user and admin
        $this->sendNotify($user->email, 'new_user', 'Admin', $shortcodes, $user->phone, $user->id, route('admin.user.edit', $user->id));
        $this->sendNotify($user->email, 'new_user', 'User', $shortcodes, $user->phone, $user->id);

        // Referred event
        event(new UserReferred($request->cookie('invite'), $user));

        if (setting('email_verification', 'permission') && !$request->has('from_social')) {
            $user->sendEmailVerificationNotification();
        }

        if (setting('referral_signup_bonus', 'permission') && (float) setting('signup_bonus', 'fee') > 0) {
            $signupBonus = (float) setting('signup_bonus', 'fee');
            $user->increment('balance', $signupBonus);
            (new Txn)->new($signupBonus, 0, $signupBonus, 'system', 'Signup Bonus', TxnType::SignupBonus, TxnStatus::Success, null, null, $user->id);
            Session::put('signup_bonus', $signupBonus);
        }

        Cookie::forget('invite');
        Auth::login($user);
        LoginActivities::add();

        return $user;
    }
}
