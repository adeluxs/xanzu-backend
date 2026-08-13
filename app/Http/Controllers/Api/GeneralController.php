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
use App\Models\Plugin;
use App\Models\Setting;
use App\Models\UserDevice;
use App\Models\WithdrawMethod;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class GeneralController extends Controller
{
    use ApiResponse;

    public function getCountries()
    {
        $location = getLocation();
        $selectedDialCode = (string) ($location->dial_code ?? '');

        $allCountry = Cache::remember('api.countries.active.v2', now()->addMinutes(30), function () {
            return Country::active()
                ->orderBy('name')
                ->get(['id', 'name', 'dial_code', 'code'])
                ->map(fn ($country) => [
                    'id' => $country->id,
                    'name' => $country->name,
                    'dial_code' => $country->dial_code,
                    'code' => $country->code,
                ])
                ->values();
        })->map(function (array $country) use ($selectedDialCode) {
            $country['selected'] = $selectedDialCode !== ''
                && (string) $country['dial_code'] === $selectedDialCode;

            return $country;
        });

        return $this->successResponse($allCountry);
    }

    public function getSettings(Request $request)
    {
        $type = $request->input('key', 'all');
        $allowedSettingNames = [
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

                // Wallet transfer feature flag (limits are exposed only from
                // the authenticated transfer-config endpoint).
                'transfer_global_status',
            ];

        // Settings are already cached centrally by the Setting model. Reusing
        // that cache removes a database hit on every mobile bootstrap request.
        $settings = collect(Setting::getAllSettings())
            ->whereIn('name', $allowedSettingNames)
            ->values()
            ->map(function ($setting) {
                return [
                    'name' => $setting->name,
                    'value' => file_exists(base_path('assets/' . $setting->val)) ? asset($setting->val) : $setting->val,
                ];
            });

        $legal_pages = Cache::remember('api.settings.legal-pages', now()->addMinutes(30), function () {
            return Page::query()
                ->where(function ($query) {
                    $query->whereIn('url', ['privacy-policy', 'terms-and-conditions'])
                        ->orWhereIn('url', ['privacy', 'terms']);
                })
                ->get(['url'])
                ->map(fn ($page) => [
                    'name' => $page->url,
                    'value' => url($page->url),
                ]);
        });

        $settings = $settings->merge($legal_pages);

        // Always expose one authoritative mobile registration flag. Relying
        // only on the generic settings collection made older/cached clients
        // miss the OTP state and show the wrong signup action.
        $settings = $settings
            ->reject(fn ($setting) => data_get($setting, 'name') === 'registration_otp_enabled')
            ->push([
                'name' => 'registration_otp_enabled',
                'value' => setting('otp_verification', 'permission') ? '1' : '0',
            ])
            ->values();

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
        $languages = Cache::remember('api.languages.active', now()->addMinutes(30), function () {
            return Language::where('status', 1)->orderByDesc('is_default')->orderBy('name')->get();
        });

        return $this->successResponse(LanguageResource::collection($languages));
    }

    public function getPlugins()
    {
        $plugins = Cache::remember('api.plugins.active', now()->addMinutes(5), static function () {
            return Plugin::query()
                ->where('status', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'status']);
        });

        return $this->successResponse($plugins);
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
        $currency = strtoupper(trim((string) $request->input('currency', '')));
        $methods = Cache::remember('api.withdraw-methods.' . ($currency ?: 'all'), now()->addMinutes(5), function () use ($currency) {
            return WithdrawMethod::where('status', 1)
                ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
                ->get();
        })->map(function ($method) {
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

        return Cache::remember(
            'api.translations.'.md5($locale.'|'.(string) @filemtime($filePath)),
            now()->addHour(),
            static fn() => json_decode(file_get_contents($filePath), true) ?: []
        );
    }

    public function getRegisterFields($type = 'user')
    {
        // Registration requirements must reflect the current admin settings.
        // Serving a stale cached field list can make the mobile form omit a
        // field that the registration validator has just made mandatory.
        $registerFields = PageSetting::select(['key', 'value'])
            ->whereNotLike('key', 'app_%')
            ->when($type === 'merchant', function ($query) {
                return $query->whereLike('key', 'merchant_%');
            }, function ($query) {
                return $query->whereNotLike('key', 'merchant_%');
            })
            ->get()
            ->map(function ($field) {
            if (str_starts_with($field->key, 'app_')) {
                $field->value = file_exists(base_path('assets/' . $field->value)) ? asset($field->value) : $field->value;
            }
            return $field;
        });

        if ($type === 'merchant') {

            // merchant kyc fields
            $merchantKycFields = Cache::remember('api.register-fields.merchant-kyc-template', now()->addMinutes(10), static fn() => Kyc::where('user_type', 'merchant')->value('fields'));

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
        $splashScreens = Cache::remember('api.app-splash-onboarding.v1', now()->addMinutes(10), function () {
            return PageSetting::whereLike('key', 'app_%')->get()->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => file_exists(base_path('assets/' . $setting->value)) ? asset($setting->value) : $setting->value,
                ];
            })->transform(function ($item) {
                $parts = explode('_', $item['key']);
                $screenKey = $parts[2] ?? 'default';
                $fieldKey = $parts[3] ?? 'value';

                return [
                    'screen' => $screenKey,
                    $fieldKey => $item['value'],
                ];
            })->groupBy('screen')->map(function ($group) {
                return $group->reduce(function ($carry, $item) {
                    return array_merge($carry, $item);
                }, []);
            })->values();
        });

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
