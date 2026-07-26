<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Enums\KYCStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\Kyc;
use App\Models\LoginActivities;
use App\Models\User;
use App\Services\KycService;
use App\Traits\ApiResponse;
use App\Traits\NotifyTrait;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    use ApiResponse, NotifyTrait;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $email = $request->input('email');
        $type = !$this->isEmail($email) ? 'username' : 'email';
        $column = $type === 'email' ? 'email' : 'username';

        $user = User::where($column, $email)->where('user_type', 'merchant')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request->email));

            return $this->validationErrorResponse(__('auth.failed'));
        } else if ($user->status == 0) {
            return $this->validationErrorResponse(__('Your account is deactivated. Please contact support.'));
        }

        $this->ensureIsNotRateLimited($type, $request);

        RateLimiter::clear($this->throttleKey($request->email));

        $token = $user->createToken('auth_token')->plainTextToken;

        LoginActivities::add($user->id);

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => [Rule::requiredIf($this->isMerchantFieldRequired('first_name')), 'nullable', 'string', 'max:255'],
            'last_name' => [Rule::requiredIf($this->isMerchantFieldRequired('last_name')), 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => [Rule::requiredIf($this->isMerchantFieldRequired('phone')), 'nullable', 'string', 'max:50', 'unique:users,phone'],
            'country' => [Rule::requiredIf($this->isMerchantFieldRequired('country')), 'nullable', 'string', 'max:255'],
            'gender' => [Rule::requiredIf($this->isMerchantFieldRequired('gender')), 'nullable', 'in:male,female,other', 'max:255'],
            'i_agree' => 'required',
            'kyc_fields' => [Rule::requiredIf($this->merchantKycRequired()), 'nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $generatedUsernameBase = trim((string) $request->first_name . ' ' . (string) $request->last_name);
            if ($generatedUsernameBase === '') {
                $generatedUsernameBase = strstr((string) $request->email, '@', true) ?: (string) $request->email;
            }

            $username = !$request->filled('username')
                ? generateUniqueUsername($generatedUsernameBase)
                : $request->username;
            $kycService = app()->make(KycService::class);
            $merchantKyc = null;

            if ($this->merchantKycRequired() || $request->filled('kyc_id')) {
                $merchantKyc = $kycService->merchantKyc($request->kyc_id);

                if (!$merchantKyc) {
                    return $this->errorResponse(__('KYC not found.'));
                }

                try {
                    $kycService->verify($merchantKyc, $request->kyc_fields ?? []);
                } catch (ValidationException $th) {
                    return $this->validationErrorResponse($th->errors());
                }
            }

            DB::beginTransaction();

            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $username,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => 'merchant',
                'kyc' => $this->merchantInitialKycStatus(),
                'gender' => $request->gender,
            ];

            $userData['country'] = $request->country ?? getLocation()['name'] ?? 'Unknown';

            $user = User::create($userData);

            if ($merchantKyc) {
                $kycService->submitKycForUser($request->kyc_fields ?? [], $merchantKyc, $user);
            }

            $this->distributeSignUpBonus($user);

            LoginActivities::add($user->id);

            DB::commit();

            if (setting('email_verification', 'permission')) {
                $this->issueEmailVerificationOtp($user);
            }

            return $this->successResponse([
                'token' => $user->createToken('auth_token')->plainTextToken,
                'token_type' => 'Bearer',
            ], 'Registration successful!');
        } catch (\Throwable $throwable) {
            \Log::error('Merchant Registration Error: ' . $throwable->getMessage());
            DB::rollBack();

            return $this->errorResponse('Sorry! Something went wrong.');
        }
    }

    public function resubmit(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        if ((int) $user->kyc !== KYCStatus::Failed->value) {
            return $this->validationErrorResponse(__('Resubmission is only allowed for rejected merchant accounts.'));
        }

        $validator = Validator::make($request->all(), [
            'first_name' => [Rule::requiredIf($this->isMerchantFieldRequired('first_name')), 'nullable', 'string', 'max:255'],
            'last_name' => [Rule::requiredIf($this->isMerchantFieldRequired('last_name')), 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => [Rule::requiredIf($this->isMerchantFieldRequired('username')), 'nullable', 'string', 'alpha_num', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => [Rule::requiredIf($this->isMerchantFieldRequired('phone')), 'nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($user->id)],
            'country' => [Rule::requiredIf($this->isMerchantFieldRequired('country')), 'nullable', 'string', 'max:255'],
            'gender' => [Rule::requiredIf($this->isMerchantFieldRequired('gender')), 'nullable', 'in:male,female,other', 'max:255'],
            'kyc_fields' => ['nullable', 'array'],
        ]);

        if ($user->email != $request->email && setting('email_verification', 'permission')) {
            $validator->after(function ($validator) use ($request, $user) {
                $validator->errors()->add('email', 'Changing email requires re-verification. Please verify your new email after submission.');
                $user->email_verified_at = null;
                $user->save();
            });
        }

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $kycService = app()->make(KycService::class);
            $merchantKyc = null;

            $merchantKyc = $kycService->merchantKyc((int) $request->kyc_id);

            if (!$merchantKyc) {
                return $this->notFoundResponse(__('KYC not found.'));
            }

            try {
                $kycService->verify($merchantKyc, $request->kyc_fields ?? [], true);
            } catch (ValidationException $th) {
                return $this->validationErrorResponse($th->errors());
            }

            DB::beginTransaction();

            $generatedUsernameBase = trim((string) $request->first_name . ' ' . (string) $request->last_name);
            if ($generatedUsernameBase === '') {
                $generatedUsernameBase = strstr((string) $request->email, '@', true) ?: (string) $request->email;
            }

            $userData = [
                'first_name' => $request->first_name ?? $user->first_name,
                'last_name' => $request->last_name ?? $user->last_name,
                'username' => $request->filled('username') ? $request->username : ($user->username ?: generateUniqueUsername($generatedUsernameBase)),
                'phone' => $request->phone ?? $user->phone,
                'email' => $request->email,
                'country' => $request->country ?? $user->country ?? getLocation()['name'] ?? 'Unknown',
                'gender' => $request->gender ?? $user->gender,
                'kyc' => KYCStatus::Pending->value,
            ];

            if ($user->email !== $request->email) {
                $userData['email_verified_at'] = null;
            }

            $user->update($userData);

            if ($merchantKyc) {
                $kycService->submitKycForUser($request->kyc_fields ?? [], $merchantKyc, $user, true);
            }

            DB::commit();

            if (setting('email_verification', 'permission') && !$user->hasVerifiedEmail()) {
                $this->issueEmailVerificationOtp($user);
            }

            return $this->successWithoutDataResponse(__('Merchant data resubmitted successfully. Please wait for admin review.'));
        } catch (\Throwable $throwable) {
            throw $throwable; // Let the exception handler deal with it and return a proper response
            \Log::error('Merchant Resubmission Error: ' . $throwable->getMessage());
            DB::rollBack();

            return $this->errorResponse(__('Sorry! Something went wrong.'));
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()?->delete();

        return $this->successWithoutDataResponse(__('Logged out'));
    }

    public function sendEmailOtp(Request $request)
    {
        if (!setting('email_verification', 'permission')) {
            return $this->validationErrorResponse('Email verification is disabled');
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->validationErrorResponse('Email already verified');
        }

        // Drop stale codes and throttle resends while a valid code exists.
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->where('created_at', '<=', Carbon::now()->subMinutes(10))
            ->delete();

        $hasActiveOtp = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->where('created_at', '>', Carbon::now()->subMinutes(10))
            ->exists();

        $isProduction = app()->isProduction();
        if ($isProduction && $hasActiveOtp) {
            return $this->validationErrorResponse('OTP already sent. Please wait before requesting again.');
        }

        $otp = $this->issueEmailVerificationOtp($user);

        return $this->successResponse([
            'otp' => $isProduction ? null : $otp,
        ], 'Email verification OTP sent successfully!');
    }

    public function verifyEmailOtp(Request $request)
    {
        if (!setting('email_verification', 'permission')) {
            return $this->validationErrorResponse('Email verification is disabled');
        }

        $validate = Validator::make($request->all(), [
            'otp' => ['required', 'digits:6'],
        ]);

        if ($validate->fails()) {
            return $this->validationErrorResponse($validate->errors()->first());
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->validationErrorResponse('Email already verified');
        }

        $otp = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->where('token', $request->otp)
            ->first();

        if (!$otp) {
            return $this->validationErrorResponse('Invalid otp');
        }

        if (Carbon::parse($otp->created_at)->lte(Carbon::now()->subMinutes(10))) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            return $this->validationErrorResponse('OTP expired. Please request a new one.');
        }

        $user->markEmailAsVerified();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return $this->successWithoutDataResponse('Email verified successfully!');
    }

    public function sendResetOtpEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $merchant = User::where('email', $request->email)
            ->where('user_type', 'merchant')
            ->first();

        if (!$merchant) {
            return $this->validationErrorResponse(__('Merchant account not found.'));
        }

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $token = random_int(100000, 999999);

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        $url = route('password.reset', ['token' => $token, 'email' => $request->email]);

        $shortcodes = [
            '[[token]]' => $token,
            '[[reset_url]]' => $url,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[full_name]]' => $merchant->full_name,
        ];

        $this->sendNotify($request->email, 'forgot_password_otp', 'User', $shortcodes, null, $merchant->id);

        return $this->successResponse([
            'otp' => app()->isProduction() ? null : $token,
        ], __('We have emailed your password reset OTP!'));
    }

    public function verifyResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $merchant = User::where('email', $request->email)
            ->where('user_type', 'merchant')
            ->first();

        if (!$merchant) {
            return $this->validationErrorResponse(__('Merchant account not found.'));
        }

        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$resetToken) {
            return $this->validationErrorResponse(__('Invalid otp'));
        }

        if (Carbon::parse($resetToken->created_at)->lte(Carbon::now()->subMinutes(10))) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return $this->validationErrorResponse(__('OTP expired. Please request a new one.'));
        }

        return $this->successWithoutDataResponse(__('OTP verified successfully'));
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->first());
        }

        $merchant = User::where('email', $request->email)
            ->where('user_type', 'merchant')
            ->first();

        if (!$merchant) {
            return $this->validationErrorResponse(__('Merchant account not found.'));
        }

        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$resetToken) {
            return $this->validationErrorResponse(__('Invalid otp'));
        }

        if (Carbon::parse($resetToken->created_at)->lte(Carbon::now()->subMinutes(10))) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return $this->validationErrorResponse(__('OTP expired. Please request a new one.'));
        }

        $merchant->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->successWithoutDataResponse(__('Password reset successfully'));
    }

    private function merchantInitialKycStatus(): int
    {
        if ($this->merchantKycRequired()) {
            return KYCStatus::Pending->value;
        }

        return KYCStatus::Verified->value;
    }

    private function merchantKycRequired(): bool
    {
        return Kyc::whereIn('user_type', ['merchant', 'both'])->exists();
    }

    private function isMerchantFieldRequired(string $field): bool
    {
        return (bool) getPageSetting('merchant_' . $field . '_show') && (bool) getPageSetting('merchant_' . $field . '_validation');
    }

    private function distributeSignUpBonus($user)
    {
        if (setting('referral_signup_bonus', 'permission') && (float) setting('signup_bonus', 'fee') > 0) {
            $signupBonus = (float) setting('signup_bonus', 'fee');
            $user->increment('balance', $signupBonus);
            (new Txn)->new($signupBonus, 0, $signupBonus, 'system', 'Signup Bonus', TxnType::SignupBonus, TxnStatus::Success, null, null, $user->id);
        }
    }

    private function isEmail($param)
    {
        return filter_var($param, FILTER_VALIDATE_EMAIL);
    }

    private function throttleKey($email)
    {
        return Str::transliterate(Str::lower($email) . '|' . request()->ip());
    }

    private function ensureIsNotRateLimited($type, Request $request)
    {
        $throttleKey = $this->throttleKey($request->email);
        if (!RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            $type => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function issueEmailVerificationOtp(User $user): int
    {
        $otp = random_int(100000, 999999);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $otp,
            'created_at' => Carbon::now(),
        ]);

        $shortcodes = [
            '[[otp]]' => $otp,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[full_name]]' => $user->full_name,
        ];

        $this->sendNotify($user->email, 'email_verification_otp', 'User', $shortcodes, null, $user->id);

        return $otp;
    }
}