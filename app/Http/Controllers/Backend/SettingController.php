<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Traits\ImageUpload;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    use ImageUpload;

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:site-setting|email-setting', ['only' => ['update']]);
        $this->middleware('permission:site-setting', ['only' => ['siteSetting', 'seoMeta', 'transferSetting']]);
        $this->middleware('permission:email-setting', ['only' => ['mailSetting']]);
    }

    /**
     * @return Application|Factory|View
     */
    public static function siteSetting()
    {
        return view('backend.setting.site_setting.index');
    }

    /**
     * @return Application|Factory|View
     */
    public static function mailSetting()
    {
        return view('backend.setting.mail');
    }

    public static function mailConnectionTest(Request $request)
    {

        try {
            Mail::raw('Testing SMTP connection successful', function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Testing SMTP connection');
            });

            notify()->success(__('SMTP connection test successful.'));

            return back();
        } catch (Exception $e) {
            notify()->error(__('SMTP connection test failed: ') . $e->getMessage(), 'Error');

            return back();
        }
    }

    /**
     * @return RedirectResponse
     */
    public function update(Request $request)
    {

        if ($request->ajax()) {
            $settingName = trim((string) $request->input('name', ''));
            if ($settingName === '') {
                Log::warning('SETTINGS_ASSET_DELETE_INVALID_NAME', [
                    'request_id' => $request->attributes->get('request_id')
                        ?: $request->header('X-Request-ID')
                        ?: (string) Str::uuid(),
                    'admin_id' => auth('admin')->id(),
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => __('The setting name is required.'),
                ], 422);
            }

            // Delete only the value actually stored in the database. Using the
            // config fallback here could remove a bundled default asset when a
            // setting record has not been created yet.
            $path = Setting::query()->where('name', $settingName)->value('val');
            $assetsDirectory = realpath(base_path('assets'));
            $assetPath = is_string($path) && $path !== ''
                ? realpath(base_path('assets/' . ltrim($path, '/\\')))
                : false;

            if (
                $assetsDirectory !== false &&
                $assetPath !== false &&
                Str::startsWith($assetPath, $assetsDirectory . DIRECTORY_SEPARATOR) &&
                is_file($assetPath)
            ) {
                @unlink($assetPath);
            }

            return response()->json([
                'success' => true,
            ]);
        }

        if ($request->has('referral_rules')) {
            Setting::updateOrCreate([
                'name' => 'referral_rules',
            ], [
                'val' => json_encode(array_values($request->get('referral_rules'))),
            ]);
            $rules = [];
        } else {
            $section = trim((string) $request->input('section', ''));
            $configuredSections = config('setting', []);

            if (
                $section === '' ||
                ! is_array($configuredSections) ||
                ! array_key_exists($section, $configuredSections)
            ) {
                Log::warning('SETTINGS_UPDATE_INVALID_SECTION', [
                    'request_id' => $request->attributes->get('request_id')
                        ?: $request->header('X-Request-ID')
                        ?: (string) Str::uuid(),
                    'admin_id' => auth('admin')->id(),
                    'section' => $section,
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                ]);

                return back()
                    ->withErrors(['section' => __('The settings section is missing or invalid.')])
                    ->withInput();
            }

            $rules = Setting::getValidationRules($section);
        }
        $data = $this->validate($request, $rules);
        $availabilityBefore = null;
        if (($section ?? null) === 'service_availability') {
            $availabilityBefore = [
                'service_suspended' => setting_enabled('service_suspended', 'service_availability', false),
                'service_suspension_message' => (string) setting('service_suspension_message', 'service_availability', ''),
            ];
        }
        try {
            $validSettings = array_keys($rules);
            foreach ($data as $key => $val) {

                if (in_array($key, $validSettings)) {
                    if ($request->hasFile($key)) {
                        $oldImage = Setting::get($key, $section);
                        $val = self::imageUploadTrait($val, $oldImage, 'setting');
                    }

                    Setting::add($key, $val, Setting::getDataType($key, $section));
                }
            }

            if ($availabilityBefore !== null) {
                $availabilityAfter = [
                    'service_suspended' => setting_enabled('service_suspended', 'service_availability', false),
                    'service_suspension_message' => (string) setting('service_suspension_message', 'service_availability', ''),
                ];
                if ($availabilityBefore !== $availabilityAfter) {
                    Log::notice('SERVICE_AVAILABILITY_CHANGED', [
                        'request_id' => $request->header('X-Request-ID') ?: (string) Str::uuid(),
                        'admin_id' => auth('admin')->id(),
                        'ip' => $request->ip(),
                        'before' => $availabilityBefore,
                        'after' => $availabilityAfter,
                    ]);
                }
            }

            if (($section ?? null) === 'transfer') {
                $syncResult = $this->syncTransferStatuses($data);

                Log::notice('TRANSFER_SETTINGS_UPDATED', [
                    'request_id' => $request->header('X-Request-ID') ?: (string) Str::uuid(),
                    'admin_id' => auth('admin')->id(),
                    'ip' => $request->ip(),
                    'global_status' => setting_enabled('transfer_global_status', 'transfer', true),
                    'buyer_status' => $syncResult['buyer']['enabled'],
                    'merchant_status' => $syncResult['merchant']['enabled'],
                    'kyc_required' => setting_enabled('transfer_require_kyc', 'transfer', false),
                    'buyer_accounts_synchronized' => $syncResult['buyer']['updated'],
                    'merchant_accounts_synchronized' => $syncResult['merchant']['updated'],
                ]);
            }

            notify()->success(__('Settings has been saved'), 'Success');

            return back();
        } catch (Exception $e) {
            notify()->error(__('Sorry, something went wrong: ') . $e->getMessage(), 'Error');

            return back();
        }
    }

    /**
     * Apply the role switches to existing users as well as future sign-ups.
     * This prevents an old per-user default from keeping merchants disabled
     * after the administrator enables merchant transfers.
     */
    private function syncTransferStatuses(array $settings): array
    {
        $result = [];

        foreach ([
            'buyer' => 'transfer_default_buyer',
            'merchant' => 'transfer_default_merchant',
        ] as $userType => $settingKey) {
            $enabled = value_is_enabled(
                $settings[$settingKey] ?? setting($settingKey, 'transfer', true)
            );

            $updated = User::query()
                ->where('user_type', $userType)
                ->where(function ($query) use ($enabled) {
                    $query->whereNull('transfer_status')
                        ->orWhere('transfer_status', '!=', $enabled);
                })
                ->update([
                    'transfer_status' => $enabled,
                    'updated_at' => now(),
                ]);

            $result[$userType] = [
                'enabled' => $enabled,
                'updated' => $updated,
            ];
        }

        return $result;
    }

    public function seoMeta()
    {
        return view('backend.setting.seo-meta');
    }

    public function transferSetting()
    {
        return view('backend.settings.transfer');
    }
}
