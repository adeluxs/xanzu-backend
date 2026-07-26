<?php

namespace App\Http\Controllers\Api;

use App\Enums\TxnType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageResource;
use App\Http\Resources\NotificationResource;
use App\Models\Country;
use App\Models\CreditLimit;
use App\Models\CreditLimitSplit;
use App\Models\Kyc;
use App\Models\Language;
use App\Models\Notification;
use App\Models\Page;
use App\Models\PageSetting;
use App\Models\Setting;
use App\Models\UserDevice;
use App\Models\WithdrawMethod;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeneralController extends Controller
{
    use ApiResponse;

    public function getCountries()
    {
        $location = getLocation();

        $allCountry = Country::active()->get(['id', 'name', 'dial_code', 'code']);

        $allCountry->map(function ($country) use ($location) {
            $country->selected = $country->dial_code == $location->dial_code;

            return $country;
        });

        return $this->successResponse($allCountry);
    }

    public function getSettings(Request $request)
    {
        $type = $request->input('key', 'all');
        $settings = Setting::select('name', 'val')

            ->whereIn('name', [
                // Branding
                'site_title',
                'site_logo',
                'site_logo_dark',
                'default_mode',

                // Currency
                'currency_symbol',
                'site_currency',
                'site_currency_type',

                // Maintenance
                'maintenance_mode',
                'maintenance_title',
                'maintenance_text',

                // Referral & Bonus
                'referral_commission',
                'referral_bonus',
                'signup_bonus',
                'referral_deposit_bonus',
                'referral_rules',
                'referral_rules_visibility',

                // Verification
                'email_verification',
                'kyc_verification',
                'fa_verification',
                'otp_verification',

                // Buyer controls
                'buyer_deposit',
                'kyc_purchase',
                'kyc_buyer_deposit',
                'buyer_purchase',

                // Flash sale
                'is_flash_sale',
                'flash_sale_status',
                'flash_sale_start_date',
                'flash_sale_end_date',

                'bnpl_take_initial_installment',
                'shipping_charge',
                'shipping_charge_type',

                // Support
                'support_email',
                'site_favicon',
            ])

            ->get()->map(function ($setting) {
                return [
                    'name' => $setting->name,
                    'value' => file_exists(base_path('assets/' . $setting->val)) ? asset($setting->val) : $setting->val,
                ];
            });

        $legal_pages = Page::whereIn('url', ['privacy-policy', 'terms-and-conditions'])->orWhereIn('url', ['privacy', 'terms'])->get()->map(function ($page) {
            return [
                'name' => $page->url,
                'value' => url($page->url),
            ];
        });

        $settings = $settings->merge($legal_pages);
        // get user from api
        if ($type == 'all') {
            $settings = $settings->merge([
                [
                    'name' => 'split_message',
                    'value' => CreditLimitSplit::splitPromoMessage(),
                ],
                [
                    'name' => 'is_bnpl_eligible',
                    'value' => (int) auth()->check() ? auth()->user()->isBnplEligible() ? "1" : "0" : "0",
                ]
            ]);
        }

        return $this->successResponse($type == 'all' ? $settings : data_get(collect($settings)->firstWhere('name', $type), 'value'));
    }

    public function getLanguages()
    {
        if (!setting('language_switcher')) {
            return $this->errorResponse('Language switcher is disabled');
        }
        $languages = Language::where('status', 1)->get();

        return $this->successResponse(LanguageResource::collection($languages));
    }

    public function getTransactionTypes()
    {
        $transactionTypes = collect(TxnType::cases())->map(function ($txnType) {
            return [
                'name' => ucwords(str_replace('_', ' ', $txnType->value)),
                'value' => $txnType->value,
            ];
        });

        return $this->successResponse($transactionTypes);
    }

    public function getWithdrawMethods(Request $request)
    {
        $methods = WithdrawMethod::where('status', 1)->when($request->currency, function ($query, $currency) {
            return $query->where('currency', $currency);
        })->get()->map(function ($method) {
            $method->fields = dynamicFieldKeyFormat($method->fields ? json_decode($method->fields, true) : []);

            $method->icon = asset($method->icon);
            $method = $method->except(['created_at', 'updated_at']);

            return $method;
        });

        return $this->successResponse($methods);
    }

    public function getNotifications(Request $request)
    {
        $user = auth()->user();
        $page = $request->page ?? 1;
        $limit = $request->per_page ?? 15;

        $notifications = Notification::where('for', 'user')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($limit, ['*'], 'page', $page);

        return $this->successResponse(data: NotificationResource::collection($notifications), message: 'Notifications fetched successfully', meta: [
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
        ]);
    }

    public function markNotificationAsRead()
    {
        auth()->user()->notifications()->update(['read' => true]);

        return response()->json([
            'status' => true,
            'message' => __('All Notifications marked as read'),
        ]);
    }

    public function registerDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'device_type' => 'required|in:android,ios',
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->first());
        }

        $user = $request->user();

        // Update or create device
        UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'device_id' => $request->device_id,
                'device_type' => $request->device_type,
                'fcm_token' => $request->fcm_token,
            ]
        );

        return $this->successWithoutDataResponse('Device registered successfully');
    }

    public function changeLanguage($locale)
    {
        session()->put('locale', $locale);

        return $this->successResponse([
            'locale' => $locale,
            'translations_keys' => $this->getTranslationKeys($locale),
        ], __('Language changed successfully'), );
    }

    private function getTranslationKeys($locale)
    {
        $filePath = resource_path("lang/app/$locale.json");
        if (!file_exists($filePath)) {
            return [];
        }

        $translations = json_decode(file_get_contents($filePath), true);

        return $translations;
    }

    public function getRegisterFields($type = 'user')
    {
        $registerFields = PageSetting::select(['key', 'value'])->whereNotLike('key', 'app_%')->when($type === 'merchant', function ($query) {
            return $query->whereLike('key', 'merchant_%');
        }, function ($query) {
            return $query->whereNotLike('key', 'merchant_%');
        })->get();

        $registerFields = $registerFields->map(function ($field) {

            if (str_starts_with($field->key, 'app_')) {
                $field->value = file_exists(base_path('assets/' . $field->value)) ? asset($field->value) : $field->value;
            }

            return $field;
        });

        if ($type === 'merchant') {

            // merchant kyc fields
            $merchantKyc = Kyc::where('user_type', 'merchant')->first();
            $merchantKycFields = $merchantKyc->fields;

            $merchantKycData = $merchantKycFields ? dynamicFieldKeyFormat(json_decode($merchantKycFields, true)) : [];

            foreach ($merchantKycData as $key => &$field) {
                unset($field['instruction_image']);
            }

            $merchantFullData = [
                'key' => 'kyc_fields',
                'value' => $merchantKycData,
            ];

            $registerFields = array_merge($registerFields->toArray(), [$merchantFullData]);

            // if merchant auth found then add auth related and submitted kyc data
            $merchantAuth = auth()->guard('merchant')->user();
            if ($merchantAuth) {
                $submittedKyc = $merchantAuth->kyc()->where('is_valid', true)->latest()->first();
                $submittedKycData = $submittedKyc ? $submittedKyc->data : null;
                $registerFields = array_merge($registerFields, [
                    [
                        'key' => 'submitted_kyc',
                        'value' => $submittedKycData,
                    ],
                ]);
            }

        }

        return response()->json([
            'status' => true,
            'data' => $registerFields,
        ]);
    }

    public function getAppSplashOnboardingScreen()
    {
        $splashScreens = PageSetting::whereLike('key', 'app_%')->get()->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => file_exists(base_path('assets/' . $setting->value)) ? asset($setting->value) : $setting->value,
            ];
        });

        $splashScreens = $splashScreens->transform(function ($item) {
            $screenKey = explode('_', $item['key'])[2];
            $fieldKey = explode('_', $item['key'])[3];

            return [
                'screen' => $screenKey,
                $fieldKey => $item['value'],
            ];
        })->groupBy('screen')->map(function ($group) {
            return $group->reduce(function ($carry, $item) {
                return array_merge($carry, $item);
            }, []);
        })->values();

        return response()->json([
            'status' => true,
            'data' => $splashScreens,
        ]);
    }

    public function getSendMoneyConfig(Request $request)
    {
        $sendMoneyController = app(SendMoneyController::class);

        return $sendMoneyController->config($request);
    }

}
