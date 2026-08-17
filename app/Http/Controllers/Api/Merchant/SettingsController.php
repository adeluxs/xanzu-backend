<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class SettingsController extends Controller
{
    use ApiResponse, ImageUpload;

    private function isMerchantFieldRequired(string $field): bool
    {
        return (bool) getPageSetting('merchant_' . $field . '_show') && (bool) getPageSetting('merchant_' . $field . '_validation');
    }

    public function getCountryDialCode($country = null)
    {
        $dialCode = collect(getCountries())->where('name', $country ?? auth()->user()->country)->first()['dial_code'] ?? null;

        return $dialCode;
    }
    public function profileUpdate(Request $request)
    {
        $user = auth()->user();
        $requestId = (string) $request->attributes->get('request_id');
        $avatar = $request->file('avatar');

        Log::info('MERCHANT_PROFILE_UPDATE_REQUEST', [
            'request_id' => $requestId,
            'user_id' => $user?->id,
            'input_fields' => array_keys($request->except(['password', 'password_confirmation', 'otp', 'token'])),
            'has_avatar' => $avatar !== null,
            'avatar_size' => $avatar?->getSize(),
            'avatar_mime' => $avatar?->getClientMimeType(),
        ]);

        $validator = Validator::make($request->all(), [
            'first_name' => [Rule::requiredIf($this->isMerchantFieldRequired('first_name')), 'nullable', 'string', 'max:255'],
            'last_name' => [Rule::requiredIf($this->isMerchantFieldRequired('last_name')), 'nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => [Rule::requiredIf($this->isMerchantFieldRequired('phone')), 'nullable', 'string', 'max:50', 'unique:users,phone,' . $user->id],
            'country' => [Rule::requiredIf($this->isMerchantFieldRequired('country')), 'nullable', 'string', 'max:255'],
            'gender' => [Rule::requiredIf($this->isMerchantFieldRequired('gender')), 'nullable', 'in:male,female,other'],
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            Log::warning('MERCHANT_PROFILE_UPDATE_VALIDATION_FAILED', [
                'request_id' => $requestId,
                'user_id' => $user?->id,
                'error_fields' => array_keys($validator->errors()->toArray()),
            ]);

            return $this->validationErrorResponse($validator->errors());
        }

        $input = $validator->validated();
        unset($input['avatar']);
        $input['date_of_birth'] = $request->filled('date_of_birth') ? $request->date_of_birth : null;

        if ($request->filled('country') && $request->country !== $user->country && $request->filled('phone')) {
            $oldDialCode = $this->getCountryDialCode($user->country);
            $newDialCode = $this->getCountryDialCode($request->country);
            if ($oldDialCode && $newDialCode) {
                $input['phone'] = str_replace($oldDialCode, $newDialCode, (string) $request->phone);
            }
        }

        $oldAvatar = $user->avatar;
        $newAvatarPath = null;

        try {
            if ($avatar !== null) {
                $newAvatarPath = self::imageUploadTrait($avatar, null);
                $input['avatar'] = $newAvatarPath;

                Log::info('MERCHANT_PROFILE_UPDATE_FILE_STORED', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'stored_avatar' => $newAvatarPath,
                ]);
            }

            $user->update($input);

            if ($newAvatarPath !== null && $oldAvatar && $oldAvatar !== $newAvatarPath) {
                $this->delete($oldAvatar);
            }

            Log::info('MERCHANT_PROFILE_UPDATE_SUCCESS', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'avatar_updated' => $newAvatarPath !== null,
            ]);

            return $this->successWithoutDataResponse(__('Profile updated successfully'));
        } catch (Throwable $throwable) {
            if ($newAvatarPath !== null) {
                $this->delete($newAvatarPath);
            }

            Log::error('MERCHANT_PROFILE_UPDATE_ERROR', [
                'request_id' => $requestId,
                'user_id' => $user?->id,
                'exception_type' => get_class($throwable),
                'exception_message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    public function twoFa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'one_time_password' => 'required_if:type,enable|nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $type = $request->input('type') ?? $request->json('type', 'enable');

        $user = auth()->user();
        if ($type == 'enable') {
            $google2fa = app('pragmarx.google2fa');
            $oneTimePassword = $request->input('one_time_password');

            if (!empty($user->google2fa_secret) && $google2fa->verifyKey($user->google2fa_secret, $oneTimePassword)) {

                $user->update([
                    'two_fa' => 1,
                ]);

                return $this->successResponse(null, __('2FA enabled successfully'));
            }

            return $this->validationErrorResponse(['one_time_password' => [__('One time key is wrong!')]]);
        } elseif ($type == 'disable') {

            if (Hash::check(request('one_time_password'), $user->password)) {
                $user->update([
                    'two_fa' => 0,
                ]);

                $user->refresh();

                return $this->successResponse(null, __('2FA disabled successfully'));
            }

            return $this->validationErrorResponse(['one_time_password' => [__('Your password is wrong!')]]);
        } elseif ($type == 'generate') {
            $google2fa = app('pragmarx.google2fa');
            $secret = $google2fa->generateSecretKey();

            $user->update([
                'google2fa_secret' => $secret,
            ]);

            return $this->successResponse([
                'qr_code' => $google2fa->getQRCodeInline(setting('site_title', 'global'), $user->email, $secret),
                'secret' => $secret,
            ], __('QR Code and Secret Key generate successfully'));
        }

        return $this->validationErrorResponse(['type' => [__('Invalid request')]]);
    }

    public function accountClose(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = auth()->user();

        if (Order::whereBelongsTo($user)->whereIn('status', [OrderStatus::WaitingForDelivery->value, OrderStatus::Delivered->value])->exists()) {
            return $this->validationErrorResponse(['reason' => [__('You have pending order. Please wait until it is completed.')]]);
        } elseif ($user->used_credit_limit_amount > 0) {
            return $this->validationErrorResponse(['reason' => [__('You have used credit limit. Please clear it before closing account.')]]);
        }

        $user->update([
            'status' => 2,
            'close_reason' => $request->reason,
        ]);

        return $this->successWithoutDataResponse(__('Your account is closed successfully'));
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return $this->validationErrorResponse(['current_password' => [__('Current password is wrong!')]]);
        }

        $user = auth()->user();
        $user->password = bcrypt($request->password);
        $user->save();

        return $this->successWithoutDataResponse(__('Password changed successfully'));
    }
}
