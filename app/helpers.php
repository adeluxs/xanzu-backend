<?php

use App\Enums\KYCStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Notification\Notify;
use App\Facades\Txn\Txn;
use App\Models\CardApplication;
use App\Models\DepositMethod;
use App\Models\Gateway;
use App\Models\Kyc;
use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\Language;
use App\Models\LevelReferral;
use App\Models\Listing;
use App\Models\Page;
use App\Models\PageSetting;
use App\Models\Plugin;
use App\Models\RecentSearch;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Fluent;

if (!function_exists('isActive')) {
    function isActive($route, $parameter = null, $classForArr = 'show')
    {

        if ($parameter != null && request()->url() === route($route, $parameter)) {
            return 'active';
        }
        if ($parameter == null && is_array($route)) {
            foreach ($route as $value) {
                if (Request::routeIs($value)) {
                    return $classForArr;
                }
            }
        }

        if ($parameter == null && Request::routeIs($route)) {
            return 'active';
        }
    }
}

if (!function_exists('tnotify')) {
    function tnotify($type, $message)
    {
        session()->flash('tnotify', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}

if (!function_exists('setting')) {
    function setting($key, $section = null, $default = null)
    {
        if (is_null($key)) {
            return new Setting;
        }

        if (is_array($key)) {

            return Setting::set($key[0], $key[1]);
        }

        $value = Setting::get($key, $section, $default);

        return is_null($value) ? value($default) : $value;
    }
}

if (!function_exists('oldSetting')) {

    function oldSetting($field, $section)
    {
        return old($field, setting($field, $section));
    }
}

if (!function_exists('settingValue')) {

    function settingValue($field)
    {
        return Setting::get($field);
    }
}

if (!function_exists('getPageSetting')) {

    function getPageSetting($key)
    {
        return PageSetting::where('key', $key)->first()?->value;
    }
}

if (!function_exists('curl_get_file_contents')) {

    function curl_get_file_contents($URL)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) {
            return $contents;
        }

        return false;
    }
}

if (!function_exists('getCountries')) {

    function getCountries()
    {
        return json_decode(file_get_contents(resource_path() . '/json/CountryCodes.json'), true);
    }
}
if (!function_exists('getCountryData')) {

    /**
     * Summary of getCountryData
     *
     * @param  mixed  $name
     * @param  mixed  $col  name code emoji unicode image dial_code
     */
    function getCountryData($name, $col = false)
    {
        $allCountries = collect(json_decode(file_get_contents(resource_path() . '/json/CountryCodes.json'), true));
        $country = $allCountries->where('name', $name)->first();

        return $col ? $country[$col] ?? null : $country;
    }
}

if (!function_exists('getCurrency')) {

    function getCurrency($countryName)
    {
        $currencies = json_decode(getJsonData('currency'), true)['fiat'];
        $currency = collect($currencies)->filter(function ($value) use ($countryName) {
            return str_contains($value['text'], $countryName);
        })->value('id', '');

        return $currency;
    }
}

if (!function_exists('getJsonData')) {

    function getJsonData($fileName)
    {
        return file_get_contents(resource_path() . "/json/$fileName.json");
    }
}


if (!function_exists('getIpAddress')) {
    function getIpAddress()
    {
        return request()->ip();
    }
}

if (!function_exists('getLocation')) {
    function getLocation()
    {
        $clientIp = (string) request()->ip();
        $isLocal = in_array($clientIp, ['127.0.0.1', '::1'], true)
            || str_starts_with($clientIp, '10.')
            || str_starts_with($clientIp, '192.168.')
            || preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $clientIp);

        // Country selection is a convenience feature and must never make the
        // app bootstrap wait indefinitely on a third-party IP service.
        if ($isLocal) {
            return app(Fluent::class, [
                'country_code' => 0,
                'name' => '',
                'dial_code' => '',
                'ip' => $clientIp,
            ]);
        }

        return Cache::remember('geo.ip.' . sha1($clientIp), now()->addHours(6), function () use ($clientIp) {
            try {
                $location = Http::connectTimeout(1)
                    ->timeout(2)
                    ->retry(1, 100, throw: false)
                    ->acceptJson()
                    ->get('http://ip-api.com/json/' . urlencode($clientIp), [
                        'fields' => 'status,countryCode,query',
                    ])
                    ->json();
            } catch (\Throwable) {
                $location = null;
            }

            if (!is_array($location) || ($location['status'] ?? 'fail') !== 'success') {
                return app(Fluent::class, [
                    'country_code' => 0,
                    'name' => '',
                    'dial_code' => '',
                    'ip' => $clientIp,
                ]);
            }

            $currentCountry = collect(getCountries())->first(
                fn ($value) => ($value['code'] ?? null) === ($location['countryCode'] ?? null)
            );

            if (!$currentCountry) {
                return app(Fluent::class, [
                    'country_code' => $location['countryCode'] ?? 0,
                    'name' => '',
                    'dial_code' => '',
                    'ip' => $location['query'] ?? $clientIp,
                ]);
            }

            return app(Fluent::class, [
                'country_code' => data_get($currentCountry, 'code', 0),
                'name' => data_get($currentCountry, 'name', ''),
                'dial_code' => data_get($currentCountry, 'dial_code', ''),
                'ip' => $location['query'] ?? $clientIp,
            ]);
        });
    }
}

if (!function_exists('gateway_info')) {
    function gateway_info($code)
    {
        $info = Gateway::where('gateway_code', $code)->first();

        return json_decode($info->credentials);
    }
}

if (!function_exists('plugin_active')) {
    function plugin_active($name)
    {
        $plugin = Plugin::where('name', $name)->where('status', true)->first();

        return $plugin;
    }
}

if (!function_exists('default_plugin')) {
    function default_plugin($type)
    {
        return Plugin::where('type', $type)->where('status', 1)->first('name')?->name;
    }
}

if (!function_exists('br2nl')) {
    function br2nl($input)
    {
        return preg_replace('/<br\\s*?\/??>/i', '', $input);
    }
}

if (!function_exists('safe')) {
    function safe($input)
    {
        if (!config('app.demo', false)) {
            return $input;
        }

        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {

            $emailParts = explode('@', $input);
            $username = $emailParts[0];

            $hiddenUsername = substr($username, 0, 2)
                . str_repeat('*', max(strlen($username) - 2, 0));

            $domain = $emailParts[1];

            $domainStart = substr($domain, 0, 2);
            $domainEnd = substr($domain, -1);

            $hiddenDomain = $domainStart
                . str_repeat('*', max(strlen($domain) - 3, 0))
                . $domainEnd;

            return $hiddenUsername . '@' . $hiddenDomain;
        }

        // Protect numbers
        return preg_replace('/(\d{3})\d{3}(\d{3})/', '$1****$2', $input);
    }
}

if (!function_exists('creditReferralBonus')) {
    function creditReferralBonus($user, $type, $mainAmount, $level = null, $depth = 1, $fromUser = null)
    {
        $LevelReferral = LevelReferral::where('type', $type)->where('the_order', $depth)->first('bounty');

        if ($user->ref_id !== null && $depth <= $level && $LevelReferral) {
            $referrer = User::find($user->ref_id);

            $bounty = $LevelReferral->bounty;
            $amount = (float) ($mainAmount * $bounty) / 100;

            $fromUserReferral = $fromUser == null ? $user : $fromUser;

            if (!$fromUserReferral || !$referrer) {
                return;
            }

            $description = str($type)->headline() . ' Referral Bonus Via ' . $fromUserReferral->full_name . ' - Level ' . $depth;

            (new Txn)->new($amount, 0, $amount, 'System', $description, TxnType::Referral, TxnStatus::Success, null, null, $referrer->id, $fromUserReferral->id, 'User', [], 'none', $depth, $type, true);

            $referrer->balance += $amount;
            $referrer->save();
            creditReferralBonus($referrer, $type, $mainAmount, $level, $depth + 1, $user);
        }
    }
}

if (!function_exists('is_custom_rate')) {
    function is_custom_rate($gateway_code)
    {
        if (in_array($gateway_code, ['nowpayments', 'coinremitter', 'blockchain'])) {
            return 'USD';
        }

        return null;
    }
}

if (!function_exists('site_theme')) {
    function site_theme()
    {
        return Cache::rememberForever('system.site_theme', function () {
            return Theme::active();
        });
    }
}
if (!function_exists('generate_date_range_array')) {
    function generate_date_range_array($startDate, $endDate): array
    {
        $startDate = Date::parse($startDate);
        $endDate = Date::parse($endDate);

        $dates = collect([]);

        while ($startDate->lte($endDate)) {
            $dates->push($startDate->format('d M'));
            $startDate->addDay();
        }

        return $dates->toArray();
    }
}
if (!function_exists('calPercentage')) {
    function calPercentage($total, $percentage)
    {
        return $total * ($percentage / 100);
    }
}

if (!function_exists('findPercentage')) {
    function findPercentage($total, $remaining)
    {
        if ($total == 0) {
            return 0;
        }

        return ($total - $remaining) / $total * 100;
    }
}

if (!function_exists('getQRCode')) {
    function getQRCode($data)
    {

        return "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=$data";
    }
}

if (!function_exists('pending_count')) {
    function pending_count()
    {
        $withdrawCount = Transaction::where('type', TxnType::Withdraw)
            ->where('status', TxnStatus::Pending)
            ->count();

        $kycCount = User::where('kyc', KYCStatus::Pending)->count();

        $depositCount = Transaction::pendingManualTxn()

            ->count();

        $ticketCount = Ticket::where('status', 'open')->count();

        $listingCount = Listing::where('is_approved', 0)->count();

        $cardApplicationCount = CardApplication::whereIn('status', ['pending', 'onhold'])->count();

        $data = [
            'withdraw_count' => $withdrawCount,
            'kyc_count' => $kycCount,
            'deposit_count' => $depositCount,
            'ticket_count' => $ticketCount,
            'listing_count' => $listingCount,
            'card_application_count' => $cardApplicationCount,
        ];

        return $data;
    }
}

if (!function_exists('content_exists')) {
    function content_exists($url)
    {
        return file_exists(base_path('assets/' . $url));
    }
}

if (!function_exists('getLandingContents')) {
    function getLandingContents($type)
    {
        $data = LandingContent::where('locale', app()->getLocale())->where('theme', site_theme())->where('type', $type)->get();

        if (!$data->count()) {
            $data = LandingContent::where('locale', defaultLocale())->where('theme', site_theme())->where('type', $type)->get();
        }
        if (!$data->count()) {
            $data = LandingContent::where('locale', defaultLocale())->where('theme', 'default')->where('type', $type)->get();
        }

        return $data;
    }
}

if (!function_exists('grettings')) {
    function grettings()
    {
        $currentHour = date('G');

        if ($currentHour >= 5 && $currentHour < 11) {
            $greeting = 'Good Morning';
        } elseif ($currentHour >= 11 && $currentHour < 14) {
            $greeting = 'Good Noon';
        } elseif ($currentHour >= 14 && $currentHour < 18) {
            $greeting = 'Good Afternoon';
        } elseif ($currentHour >= 18 && $currentHour < 24) {
            $greeting = 'Good Evening';
        } else {
            $greeting = 'Good Evening';
        }

        return $greeting;
    }
}

if (!function_exists('nextInstallment')) {
    function nextInstallment($id, $model, $conditionField)
    {
        $trx = $model::where($conditionField, $id)->where('given_date', null)->first();

        return $trx !== null ? date('d M Y', strtotime($trx->installment_date)) : '--';
    }
}

if (!function_exists('defaultLocale')) {
    function defaultLocale()
    {
        $language = Language::where('is_default', true)->first();

        return $language->locale ?? 'en';
    }
}

if (!function_exists('localeName')) {
    function localeName()
    {
        return Language::where('locale', App::currentLocale())->first()?->name;
    }
}

if (!function_exists('getLandingData')) {
    function getLandingData($code, $status = true)
    {
        $data = LandingPage::where('locale', app()->getLocale())->where('theme', site_theme())->when($status != 'both', function ($query) use ($status) {
            $query->where('status', $status);
        })->where('code', $code)->first();
        if (!$data) {
            $data = LandingPage::where('locale', defaultLocale())->where('status', $status)->where('code', $code)->first();
        }
        if (!$data) {
            $data = LandingPage::where('locale', app()->getLocale())->where('status', $status)->where('code', $code)->first();
        }
        if (!$data) {
            $data = LandingPage::where('locale', defaultLocale())->where('status', $status)->where('code', $code)->first();
        }

        return $data;
    }
}

if (!function_exists('isRtl')) {
    function isRtl($code)
    {
        $language = Language::where('locale', $code)->first();

        return $language->is_rtl ?? false;
    }
}

if (!function_exists('isPlusTransaction')) {
    function isPlusTransaction($type)
    {
        if (
            $type == TxnType::Subtract ||
            $type == TxnType::Withdraw || $type == TxnType::WithdrawAuto
            || $type == TxnType::Refund || $type == TxnType::PlanPurchased || $type == TxnType::ProductOrder
            || $type == TxnType::ProductOrderViaTopup
            || $type == TxnType::BnplInstallment
        ) {
            return false;
        }

        return true;
    }
}

if (!function_exists('getTotalMature')) {
    function getTotalMature($dps)
    {
        $totalInstallmentFee = $dps->transactions->sum('paid_amount');

        $interestAmount = ($totalInstallmentFee * $dps->plan?->interest_rate) / 100;

        return intval($totalInstallmentFee + $interestAmount);
    }
}

if (!function_exists('getBrowser')) {

    function getBrowser($user_agent = null)
    {

        $user_agent = $user_agent != null ? $user_agent : request()->userAgent();

        $browser = 'Unknown';
        $platform = 'Unknown';

        if (preg_match('/linux/i', $user_agent)) {
            $platform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            $platform = 'Mac';
        } elseif (preg_match('/windows|win32/i', $user_agent)) {
            $platform = 'Windows';
        } elseif (preg_match('/windows|win32/i', $user_agent)) {
            $platform = 'Windows';
        }

        if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
            $browser = 'IE';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/OPR/i', $user_agent)) {
            $browser = 'Opera';
        } elseif (preg_match('/Chrome/i', $user_agent) && !preg_match('/Edge/i', $user_agent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $user_agent) && !preg_match('/Edge/i', $user_agent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Netscape/i', $user_agent)) {
            $browser = 'Netscape';
        } elseif (preg_match('/Edge/i', $user_agent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Trident/i', $user_agent)) {
            $browser = 'IE';
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
        ];
    }
}

if (!function_exists('mySqlVersion')) {
    function mySqlVersion()
    {
        $pdo = DB::connection()->getPdo();
        $version = $pdo->query('select version()')->fetchColumn();

        preg_match("/^[0-9\.]+/", $version, $match);

        $version = $match[0];

        return $version;
    }
}

if (!function_exists('notify')) {
    function notify(?string $message = null, ?string $title = null)
    {
        $notify = app(Notify::class);

        if (!is_null($message)) {
            return $notify->success($message, $title);
        }

        return $notify;
    }
}

if (!function_exists('getTransactionIcon')) {
    function getTransactionIcon($type)
    {
        return match ($type) {
            TxnType::Deposit || TxnType::ManualDeposit => '<i class="icon-dollar-square"></i>',
            TxnType::Withdraw || TxnType::WithdrawAuto => '<i class="icon-money-send"></i>',
            TxnType::Refund => '<i class="icon-money-change"></i>',
            TxnType::PlanPurchased => '<i class="icon-crown"></i>',
            TxnType::Referral => '<i class="icon-profile-2user"></i>',
            TxnType::SignupBonus => '<i class="icon-gift"></i>',
            TxnType::Subtract => '<i class="icon-money-remove"></i>',
            TxnType::BnplInstallment => '<i class="icon-money-remove"></i>',
            default => '<i class="icon-arrange-square"></i>'
        };
    }
}

if (!function_exists('isMenuOpen')) {
    function isMenuOpen($route = null, $parameter = null, $classForArr = 'open')
    {
        if ($parameter != null && request()->url() === route($route, $parameter)) {
            return 'active';
        }
        if ($parameter == null && is_array($route)) {
            foreach ($route as $value) {
                if (Request::routeIs($value)) {
                    return $classForArr;
                }
            }
        }

        if ($parameter == null && Request::routeIs($route)) {
            return 'active';
        }
    }
}
if (!function_exists('themeAsset')) {
    /**
     * Summary of themeAsset
     *
     * @param  mixed  $assetPath
     */
    function themeAsset($assetPath, $theme = null)
    {
        return asset(sprintf("frontend/%s/$assetPath", $theme ?? site_theme())) . '?v=' . config('app.version');
    }
}

if (!function_exists('orderService')) {
    function orderService()
    {
        return app(OrderService::class);
    }
}

if (!function_exists('orderDateFormat')) {
    function orderDateFormat($date)
    {
        return now()->parse($date)->format('d M Y, h:i:s A');
    }
}

if (!function_exists('getSellerKyc')) {
    function getSellerKyc()
    {
        return Cache::remember('seller_kyc', 30, function () {
            return Kyc::sellerVerification()->first();
        });
    }
}

if (!function_exists('sellerHasKyc')) {
    function sellerHasKyc()
    {
        $sellerKyc = getSellerKyc();
        $sellerKycId = $sellerKyc?->id;

        if (!$sellerKycId || !$sellerKyc->status) {
            return true;
        }

        return auth('web')->user()?->sellerKyc($sellerKycId)?->exists();
    }
}
/**
 * Summary of gatewayPayAmount
 *
 * @param  mixed  $gateway_code
 * @param  mixed  $finalPrice
 * @return array payAmount,charge,finalAmount,gatewayInfo
 */
if (!function_exists('gatewayPayAmount')) {
    function gatewayPayAmount($gateway_code, $finalPrice)
    {
        $finalPrice = floatval(is_numeric($finalPrice) ? $finalPrice : 0);

        $gatewayInfo = DepositMethod::code($gateway_code)->first();
        if (!$gatewayInfo) {
            return [$finalPrice, 0, $finalPrice, null];
        }
        $charge = $gatewayInfo->charge_type == 'percentage' ? (($gatewayInfo->charge / 100) * $finalPrice) : $gatewayInfo->charge;
        $finalAmount = (float) $finalPrice + (float) $charge;
        $payAmount = $finalAmount * $gatewayInfo->rate;

        return [$payAmount, $charge, $finalAmount, $gatewayInfo];
    }
}

if (!function_exists('processManualDepositData')) {
    /**
     * Process manual deposit data from the request.
     *
     * @param  Illuminate\Http\Request  $request
     * @return array|null
     */
    function processManualDepositData($request, $field = 'manual_data')
    {
        $manualData = null;

        if ($request->$field != null && is_array($request->$field)) {
            $manualData = $request->$field ?? [];
            foreach ($manualData as $key => $value) {
                if (is_file($value)) {
                    $manualData[$key] = orderService()->imageUploadTrait($value);
                }
            }
        }

        return $manualData;
    }
}

if (!function_exists('validateManualGatewayCustomFields')) {
    /**
     * Validate required custom fields for manual gateways.
     *
     * The caller passes the exact payload path to validate, so the helper does
     * not need to guess which request shape is being used.
     */
    function validateManualGatewayCustomFields($request, $validator, ?string $gatewayCode = null, string $fieldPath = 'customFields'): void
    {
        $gatewayCode = $gatewayCode ?? (string) $request->input('gateway_code', '');

        if ($gatewayCode === '' || $gatewayCode === 'balance') {
            return;
        }

        $gateway = \App\Models\DepositMethod::where('gateway_code', $gatewayCode)->first();
        if (!$gateway || !$gateway->field_options || $gateway->type !== 'manual') {
            return;
        }

        foreach (($gateway->custom_fields ?? []) as $key => $field) {
            $fieldId = data_get($field, 'id', $key);
            $fieldName = (string) data_get($field, 'name', $fieldId);
            $fieldValidation = (string) data_get($field, 'validation', '');
            $isRequired = str_contains($fieldValidation, 'required');

            if (!$isRequired) {
                continue;
            }

            $value = $request->input("{$fieldPath}.{$fieldId}");
            if ($value === null || $value === '') {
                $value = $request->input("{$fieldPath}.{$fieldName}");
            }

            $hasFile = $request->hasFile("{$fieldPath}.{$fieldId}")
                || $request->hasFile("{$fieldPath}.{$fieldName}");

            if (!$hasFile && trim((string) $value) === '') {
                $validator->errors()->add(
                    "{$fieldPath}.{$fieldId}",
                    __('The :field field is required for the selected payment method.', ['field' => $fieldName])
                );
            }
        }
    }
}

if (!function_exists('highlightColor')) {
    function highlightColor($text, $class = 'highlight')
    {
        return preg_replace_callback('/\[\[color_text=(.*?)\]\]/', function ($matches) use ($class) {
            return '<span class="' . $class . '">' . $matches[1] . '</span>';
        }, $text);
    }
}

if (!function_exists('getRecentSearch')) {
    function getRecentSearch()
    {
        $authSearches = [];
        if (auth()->check()) {
            $authSearches = auth()->user()->recentSearches()->latest()->get()->pluck('query')->toArray();
        }

        return array_unique(array_merge($authSearches, session('searched', [])));
    }
}

if (!function_exists('getTopSearch')) {
    function getTopSearch()
    {
        return RecentSearch::latest(column: 'count')->get();
    }
}

if (!function_exists('bsToAdminBadges')) {
    function bsToAdminBadges($badge)
    {
        return str($badge)->replace(['badge', 'warning', 'info', 'error', 'bg-warning'], ['site-badge', 'pending', 'primary text-white', 'danger', 'pending'])->remove('bg-');
    }
}

if (!function_exists('amountWithCurrency')) {
    function amountWithCurrency($amount, $currency = null)
    {
        $currency = $currency ?? setting('currency_symbol', 'global');

        $currencySymbol = setting('currency_symbol', 'global');
        if ($currency == $currencySymbol) {
            $currencySymbol = $currency;
            $currency = '';
        } else {
            $currencySymbol = '';
        }

        return $currencySymbol . number_format($amount, 2) . ' ' . $currency;
    }
}

if (!function_exists('getPageData')) {
    function getPageData($page)
    {
        $data = Page::where('code', $page)->where('locale', app()->getLocale())->where('theme', site_theme())->first();
        if (!$data) {
            $data = Page::where('code', $page)->where('locale', defaultLocale())->where('theme', site_theme())->first();
        }
        if (!$data) {
            $data = Page::where('code', $page)->where('locale', app()->getLocale())->where('theme', 'default')->first();
        }
        if (!$data) {
            $data = Page::where('code', $page)->where('locale', defaultLocale())->where('theme', 'default')->first();
        }

        return $data;
    }
}

if (!function_exists('getLandingPageData')) {
    function getLandingPageData()
    {
        $data = LandingPage::where('locale', app()->getLocale())->where('theme', site_theme());
        if (!$data->count()) {
            $data = LandingPage::where('locale', defaultLocale())->where('theme', site_theme());
        }
        if (!$data->count()) {
            $data = LandingPage::where('locale', app()->getLocale())->where('theme', 'default');
        }
        if (!$data->count()) {
            $data = LandingPage::where('locale', defaultLocale())->where('theme', 'default');
        }

        return $data;
    }
}

if (!function_exists('isDevMode')) {
    function isDevMode()
    {
        $request = request();

        return
            in_array($request->ip(), ['127.0.0.1', 'localhost', '::1', '192.168.1.1']) ||
            str($request->server('SERVER_NAME'))->contains(['localhost', '127.0.0.1', '::1', '192.168.1.1', 'orexcoin.test']);
    }
}

if (!function_exists('isWishlisted')) {
    function isWishlisted($id)
    {
        return in_array($id, session('wishlist') ?? []);
    }
}

if (!function_exists('hexToRgb')) {
    function hexToRgb($hex)
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }
}

if (!function_exists('isFollowing')) {
    function isFollowing($user)
    {
        return auth()->user()->following->contains($user);
    }
}

if (!function_exists('defaultAvatar')) {
    function defaultAvatar()
    {
        return asset('frontend/' . site_theme() . '/images/user/user-default.png');
    }
}

if (!function_exists('frontendUrl')) {
    function frontendUrl($path = '')
    {
        $baseUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        if ($path === null || $path === '') {
            return $baseUrl;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return $baseUrl . '/' . ltrim((string) $path, '/');
    }
}

if (!function_exists('frontendPanelPath')) {
    function frontendPanelPath($route, $parameter = null, ?bool $seller = null)
    {
        $isSeller = $seller ?? (auth()->check() && auth()->user()->is_seller);
        $prefix = $isSeller ? 'seller' : 'user';

        $mappedPaths = [
            'dashboard' => 'dashboard',
            'transactions' => 'transactions/{type?}',
            'topup.index' => 'topup',
            'deposit.index' => 'deposit',
            'deposit.now' => 'deposit',
            'deposit.amount' => 'deposit',
            'payment.index' => 'payment',
            'payment.withdraw.index' => 'payment/payout',
            'payment.withdraw.account.index' => 'payment/payout/account',
            'setting.show' => 'settings',
            'setting.index' => 'settings',
            'referral' => 'referral',
            'kyc' => 'kyc',
            'kyc.submission' => 'kyc/submission/{0}',
            'coupon.index' => 'coupon',
            'ticket.index' => 'support-ticket/index',
            'ticket.show' => 'support-ticket/show/{0}',
            'purchase.index' => 'purchase',
            'purchase.success' => 'purchase/success/{0}',
            'purchase.invoice' => 'purchase/invoice/{0}',
            'subscriptions.history' => 'subscriptions/history',
        ];

        $path = $mappedPaths[$route] ?? str_replace('.', '/', (string) $route);
        $parameters = is_array($parameter) ? $parameter : ($parameter !== null ? [0 => $parameter] : []);

        $path = preg_replace_callback('/\{([^}]+)\}/', function ($matches) use (&$parameters) {
            $rawKey = $matches[1];
            $optional = str_ends_with($rawKey, '?');
            $key = rtrim($rawKey, '?');

            if (array_key_exists($key, $parameters)) {
                $value = $parameters[$key];
                unset($parameters[$key]);

                return rawurlencode((string) $value);
            }

            if (ctype_digit($key) && array_key_exists((int) $key, $parameters)) {
                $value = $parameters[(int) $key];
                unset($parameters[(int) $key]);

                return rawurlencode((string) $value);
            }

            if (!empty($parameters)) {
                $firstKey = array_key_first($parameters);
                $value = $parameters[$firstKey];
                unset($parameters[$firstKey]);

                return rawurlencode((string) $value);
            }

            return $optional ? '' : '';
        }, $path);

        $path = preg_replace('#/\?#', '?', $path);
        $path = preg_replace('#//+#', '/', trim($prefix . '/' . $path, '/'));
        $path = rtrim($path, '/');

        if (!empty($parameters)) {
            $path .= '?' . http_build_query($parameters);
        }

        return $path;
    }
}

if (!function_exists('frontendPanelUrl')) {
    function frontendPanelUrl($route, $parameter = null, ?bool $seller = null)
    {
        return frontendUrl(frontendPanelPath($route, $parameter, $seller));
    }
}

if (!function_exists('buyerSellerRoute')) {
    function buyerSellerRoute($route, $parameter = null)
    {
        $routeName = (auth()->check() && auth()->user()->is_seller ? 'seller' : 'user') . '.' . $route;

        if (Route::has($routeName)) {
            return route($routeName, $parameter);
        }

        return frontendPanelUrl($route, $parameter);
    }
}

if (!function_exists('to_buyerSellerRoute')) {
    function to_buyerSellerRoute($route, $parameter = null)
    {
        $routeName = (auth()->check() && auth()->user()->is_seller ? 'seller' : 'user') . '.' . $route;

        if (Route::has($routeName)) {
            return to_route($routeName, $parameter);
        }

        return redirect()->away(frontendPanelUrl($route, $parameter));
    }
}

if (!function_exists('topupDepositText')) {
    function topupDepositText($user)
    {
        return ($user ?? auth()->user())->is_seller ? __('Deposit') : __('Top Up');
    }
}

if (!function_exists('isDashboardRoute')) {
    function isDashboardRoute($withBuyer = false)
    {
        $isDashboard = Route::is('seller.*') && !Route::is('seller.details');

        return $isDashboard || ($withBuyer ? Route::is('user.*') : false);
    }
}
if (!function_exists('sideSingleItem')) {
    function sideSingleItem($type, $name, $urlOverride, $iconMap, $isActiveExtra = false)
    {
        $iconFull = $iconMap[$type] ?? 'lucide:circle';
        $isActive = $isActiveExtra;
        $activeClass = $isActive ? 'active' : '';
        $href = $urlOverride;

        return
            '<li class="slide ' . $activeClass . '">
        <a href="' . $href . '" class="sidebar-menu-item">
        <span class="side-menu-icon">
        <iconify-icon icon="' . $iconFull . '" class="dashbaord-icon"></iconify-icon>
        </span>
        <span class="sidebar-menu-label">' . $name . '</span>
        </a>
        </li>';
    }
}

if (!function_exists('isPlanModuleEnabled')) {
    function isPlanModuleEnabled()
    {
        return setting('seller_subscription', 'permission');
    }

}

if (!function_exists('generateUniqueUsername')) {
    function generateUniqueUsername($name)
    {
        do {
            $username = str_replace(' ', '', strtolower($name)) . rand(1000, 9999);
        } while (User::where('username', $username)->exists());

        return $username;
    }
}

if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber($phone, $dialCode = null, $withPlus = true, $skipDialCode = false)
    {
        $rawPhone = trim((string) $phone);
        $rawDialCode = trim((string) $dialCode);

        // Keep digits only so spaces, brackets and dashes entered by mobile
        // keyboards do not create different identities for the same number.
        $phoneDigits = preg_replace('/\D+/', '', $rawPhone) ?? '';
        $dialDigits = preg_replace('/\D+/', '', $rawDialCode) ?? '';

        if ($dialDigits !== '' && str_starts_with($phoneDigits, $dialDigits)) {
            $phoneDigits = substr($phoneDigits, strlen($dialDigits));
        }

        if ($skipDialCode) {
            return $phoneDigits;
        }

        $full = $dialDigits.$phoneDigits;
        if ($full === '') {
            return '';
        }

        return ($withPlus ? '+' : '').$full;
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = null, $thousandSeparatorRemove = false)
    {
        $currency = $currency ?? setting('currency_symbol', 'global');
        $currency = $currency == setting('site_currency', 'global') ? setting('currency_symbol', 'global') : $currency;
        $amount = number_format($amount, 2);

        return $currency . ($thousandSeparatorRemove ? str_replace(',', '', $amount) : $amount);
    }
}
if (!function_exists('dynamicFieldKeyFormat')) {
    function dynamicFieldKeyFormat(array $array, $json = false): array|bool|string
    {
        foreach ($array as $key => $value) {
            $value['id'] = $key;
            $array[$key] = $value;
        }
        $array = array_values($array);

        return $json ? json_encode($array) : $array;
    }
}
if (!function_exists('needJsonResponse')) {
    function needJsonResponse()
    {
        $request = request();
        $requestHeader = $request->header('X-Requested-With');

        return $request->is('api/*') || $request->expectsJson() || array_intersect(array_values(config('app.apk_package')), is_array($requestHeader) ? $requestHeader : [$requestHeader]);
    }

}