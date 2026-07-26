<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ProviderPlatform;
use App\Enums\KYCStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\Kyc;
use App\Models\LevelReferral;
use App\Models\Listing;
use App\Models\Provider;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserKyc;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    use ImageUpload;
    use NotifyTrait;

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:customer-list|customer-login|customer-mail-send|customer-basic-manage|customer-change-password|all-type-status|customer-balance-add-or-subtract', ['only' => ['index', 'activeUser', 'disabled', 'mailSendAll', 'mailSend']]);
        $this->middleware('permission:customer-basic-manage|customer-change-password|all-type-status|customer-balance-add-or-subtract', ['only' => ['edit']]);
        $this->middleware('permission:customer-login', ['only' => ['userLogin']]);
        $this->middleware('permission:customer-mail-send', ['only' => ['mailSendAll', 'mailSend']]);
        $this->middleware('permission:customer-basic-manage', ['only' => ['update']]);
        $this->middleware('permission:customer-change-password', ['only' => ['passwordUpdate']]);
        $this->middleware('permission:all-type-status', ['only' => ['statusUpdate']]);
        $this->middleware('permission:customer-balance-add-or-subtract', ['only' => ['balanceUpdate']]);
    }

    public function index(Request $request)
    {
        $search = $request->query('query') ?? null;

        $users = User::query()
            ->unless(blank(request('email_status')), function ($query) {
                if (request('email_status')) {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            })
            ->when(filled(request('kyc_status')), function ($query) {
                $query->where('kyc', request('kyc_status'));
            })
            ->when(filled(request('status')), function ($query) {
                $query->where('status', request('status'));
            })
            ->when(filled(request('type')), function ($query) {
                match (request('type')) {
                    'merchant' => $query->where('user_type', 'merchant'),
                    'buyer' => $query->where('user_type', 'buyer'),
                    default => null,
                };

            })
            ->when(filled(request('sort_field')), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->when(!request()->has('sort_field'), function ($query) {
                $query->latest();
            })
            ->search($search)
            ->paginate();

        $title = __('All Users');

        return view('backend.user.index', compact('users', 'title'));
    }

    public function buyerUser(Request $request)
    {
        $search = $request->query('query') ?? null;

        $users = User::when(filled(request('email_status')), function ($query) {
            if (request('email_status')) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        })
            ->when(filled(request('kyc_status')), function ($query) {
                $query->where('kyc', request('kyc_status'));
            })
            ->when(filled(request('status')), function ($query) {
                $query->where('status', request('status'));
            })
            ->when(filled(request('sort_field')), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->when(!request()->has('sort_field'), function ($query) {
                $query->latest();
            })
            ->search($search)
            ->where('user_type', 'buyer')
            ->paginate();

        $title = __('Buyers');

        return view('backend.user.index', compact('users', 'title'));
    }

    public function merchantUser(Request $request)
    {
        $search = $request->query('query') ?? null;

        $users = User::when(filled(request('email_status')), function ($query) {
            if (request('email_status')) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        })
            ->when(filled(request('kyc_status')), function ($query) {
                $query->where('kyc', request('kyc_status'));
            })
            ->when(filled(request('status')), function ($query) {
                $query->where('status', request('status'));
            })
            ->when(filled(request('sort_field')), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->when(!request()->has('sort_field'), function ($query) {
                $query->latest();
            })
            ->search($search)
            ->where('user_type', 'merchant')
            ->paginate();

        $title = __('Merchants');

        return view('backend.user.index', compact('users', 'title'));
    }

    public function approvedMerchantUser(Request $request)
    {
        return $this->merchantUserByKycStatus($request, KYCStatus::Verified->value, __('Approved Merchants'));
    }

    public function requestMerchantUser(Request $request)
    {
        return $this->merchantUserByKycStatus($request, KYCStatus::Pending->value, __('Merchant Requests'));
    }

    public function rejectedMerchantUser(Request $request)
    {
        return $this->merchantUserByKycStatus($request, KYCStatus::Failed->value, __('Rejected Merchants'));
    }

    protected function merchantUserByKycStatus(Request $request, int $kycStatus, string $title)
    {
        $search = $request->query('query') ?? null;

        $users = User::when(filled(request('email_status')), function ($query) {
            if (request('email_status')) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        })
            ->when(filled(request('status')), function ($query) {
                $query->where('status', request('status'));
            })
            ->when(filled(request('sort_field')), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->when(!request()->has('sort_field'), function ($query) {
                $query->latest();
            })
            ->search($search)
            ->where('user_type', 'merchant')
            ->where('kyc', $kycStatus)
            ->paginate();

        return view('backend.user.index', compact('users', 'title'));
    }

    public function create(Request $request, $type = null)
    {
        $userType = $type ?? str(url()->current())->after('add-new/')->value();
        $kycs = Kyc::where('status', true)->when($userType, function ($query) use ($userType) {
            $query->where('user_type', $userType)->orWhere('user_type', 'both');
        })->get();
        return view('backend.user.create', compact('kycs', 'userType'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $user = User::withCount(['tickets', 'referrals', 'listings'])->findOrFail($id);
        $level = LevelReferral::where('type', 'investment')->max('the_order') + 1;

        $tab = request('tab');
        $query = request('query');
        $sortField = request('sort_field');
        $sortDir = request('sort_dir') ?? 'desc';

        $earnings = [];
        $transactions = [];
        $listings = $purchases = [];
        $tickets = [];
        $ads_histories = [];
        $ads = [];
        match ($tab) {
            'transactions' => $transactions = Transaction::where('user_id', $user->id)
                ->when($query, fn($q) => $q->search($query))
                ->when($sortField, fn($q) => $q->orderBy($sortField, $sortDir), fn($q) => $q->latest())
                ->paginate()
                ->withQueryString(),
            'earnings' => $earnings = Transaction::where('user_id', $user->id)
                ->whereIn('type', [TxnType::Referral, TxnType::ProductSold, TxnType::SignupBonus])
                ->when($query, fn($q) => $q->search($query))
                ->when($sortField, fn($q) => $q->orderBy($sortField, $sortDir), fn($q) => $q->latest())
                ->paginate()
                ->withQueryString(),

            'ticket' => $tickets = Ticket::where('user_id', $user->id)
                ->when($query, fn($q) => $q->where('title', 'LIKE', '%' . $query . '%'))
                ->when(in_array($sortField, ['created_at', 'title', 'status']), fn($q) => $q->orderBy($sortField, $sortDir), fn($q) => $q->latest())
                ->paginate()
                ->withQueryString(),
            'listings' => $listings = Listing::whereBelongsTo($user, 'seller')
                ->when($query, fn($q) => $q->search($query))
                ->latest()->paginate(),
            'purchase' => $purchases = Transaction::own($user)
                ->when($query, fn($q) => $q->search($query))
                ->whereIn('type', [TxnType::ProductOrder, TxnType::ProductOrderViaTopup])->latest()->paginate(),
            default => null,
        };
        $statistics = [
            'total_earnings' => $user->totalProfit(),
            'total_transactions' => $user->transaction->count(),
            'total_topup' => $user->is_seller ? $user->balance : $user->balance,
            'total_withdraw' => $user->totalWithdraw(),
            'total_tickets' => $user->tickets_count,
            'earnings' => $user->totalProfit(),
            'all_referral' => $user->referrals_count,
            'total_listings' => $user->listings_count,
        ];

        return view('backend.user.edit', compact('user', 'level', 'statistics', 'earnings', 'listings', 'transactions', 'tickets', 'purchases', 'ads_histories', 'ads'));
    }

    /**
     * @return RedirectResponse
     */
    public function statusUpdate($id, Request $request)
    {
        $input = $request->all();

        $user = User::find($id);
        $validator = Validator::make($input, [
            'status' => ['required'],
            'email_verified' => ['required'],
            'kyc' => ['required'],
            'two_fa' => ['required'],
            'deposit_status' => ['nullable'],
            'withdraw_status' => ['required'],
            'otp_status' => ['nullable'],
            'card_status' => ['nullable'],
            'user_type' => ['nullable'],
            'phone_verified' => ['nullable'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $data = [
            'status' => $input['status'],
            'kyc' => intval($input['kyc'] ?? KYCStatus::Failed),
            'two_fa' => $input['two_fa'],
            'deposit_status' => $input['deposit_status'] ?? $user->deposit_status,
            'withdraw_status' => $input['withdraw_status'],
            'otp_status' => $input['otp_status'] ?? $user->otp_status,
            'card_status' => $input['card_status'] ?? $user->card_status,
            'email_verified_at' => $input['email_verified'] == 1 ? now() : null,
            'user_type' => $input['user_type'] ?? $user->user_type,
            'phone_verified_at' => array_key_exists('phone_verified', $input) ? ($input['phone_verified'] == 1 ? now() : null) : $user->phone_verified_at,
        ];

        if ($user->status != $input['status'] && !$input['status']) {

            $shortcodes = [
                '[[full_name]]' => $user->full_name,
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_url]]' => route('home'),
            ];

            $this->sendNotify($user->email, 'user_account_disabled', 'User', $shortcodes, $user->phone, $user->id);
        }

        User::find($id)->update($data);

        if ($user->wasChanged('kyc')) {
            $oldLocal = KYCStatus::tryFrom($user->getOriginal('kyc'));

            if ($oldLocal != KYCStatus::Verified && $data['kyc'] == KYCStatus::Verified->value) {
                // add credit limit assigned template
                app(CronJobController::class)->processCreditLimit($user, true);
            }
        }

        if (!$input['kyc']) {
            $this->markAsUnverified($id);
        }

        notify()->success(__('Status Updated Successfully'), 'Success');

        return back();

    }

    protected function markAsUnverified($user_id)
    {
        UserKyc::where('user_id', $user_id)->where('is_valid', true)->update([
            'is_valid' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fname' => ['required', 'string', 'max:255'],
            'lname' => ['required', 'string', 'max:255'],
            'username' => ['required_if:user_type,merchant', 'nullable', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'date_of_birth' => ['required_if:user_type,merchant', 'nullable', 'date'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required_unless:user_type,merchant', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'min:6'],
            'invite' => ['nullable', 'exists:referral_links,code'],
            'country' => ['required_unless:user_type,merchant'],
            'gender' => ['required_if:user_type,merchant', 'nullable', 'in:male,female,other'],
            'city' => ['required_if:user_type,merchant', 'nullable', 'string', 'max:255'],
            'zip_code' => ['required_if:user_type,merchant', 'nullable', 'string', 'max:50'],
            'address' => ['required_if:user_type,merchant', 'nullable', 'string', 'max:1000'],
            'user_type' => ['required', 'in:merchant,buyer'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'string', 'max:255'],
            'provider_name' => ['required_if:user_type,merchant', 'nullable', 'string', 'max:255'],
            'provider_status' => ['required_if:user_type,merchant', 'nullable', 'in:0,1'],
            'provider_website_url' => ['nullable', 'url', 'max:255'],
            'provider_platform' => ['nullable', Rule::in(array_column(ProviderPlatform::cases(), 'value'))],
            'provider_platform_host' => ['nullable', 'string', 'max:255'],
            'provider_api_key' => ['nullable', 'string', 'max:255'],
            'provider_api_secret' => ['nullable', 'string', 'max:255'],
            'provider_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'provider_cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'provider_description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        $generateUsername = str($request->fname . $request->lname)->lower()->value();
        $requestedUsername = str((string) $request->username)->lower()->value();
        $baseUsername = $request->filled('username') ? $requestedUsername : $generateUsername;
        $usernameExists = User::where('username', $baseUsername)->exists();

        $user = User::create([
            'first_name' => $request->fname,
            'last_name' => $request->lname,
            'username' => !$usernameExists ? $baseUsername : $baseUsername . Str::random(4),
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country ?? getLocation()['name'] ?? 'Unknown',
            'gender' => $request->gender,
            'date_of_birth' => $request->date('date_of_birth'),
            'city' => $request->city,
            'zip_code' => $request->zip_code,
            'address' => $request->address,
            'ref_id' => empty($request->invite) ? null : $request->invite,
            'password' => bcrypt($request->password),
            'status' => 1,
            'two_fa' => 0,
            'deposit_status' => 1,
            'withdraw_status' => 1,
            'otp_status' => 0,
            'referral_status' => 1,
            'email_verified_at' => now(),
            'kyc' => KYCStatus::NOT_SUBMITTED,
            'user_type' => $request->user_type,
            'public_key' => $request->api_key,
            'secret_key' => $request->secret_key,
            'webhook_secret' => $request->webhook_secret,
        ]);

        if ($request->user_type === 'merchant') {
            $providerImagePath = null;
            if ($request->hasFile('provider_image')) {
                $providerImagePath = $this->imageUploadTrait(query: $request->file('provider_image'), folder: 'providers/');
            }

            $providerCoverImagePath = null;
            $coverImageFile = $request->file('cover_image') ?: $request->file('provider_cover_image');
            if ($coverImageFile) {
                $providerCoverImagePath = $this->imageUploadTrait(query: $coverImageFile, folder: 'providers/covers/');
            }

            Provider::create([
                'user_id' => $user->id,
                'name' => $request->provider_name,
                'image' => $providerImagePath,
                'cover_image' => $providerCoverImagePath,
                'website_url' => $request->provider_website_url,
                'platform' => $request->provider_platform ?: ProviderPlatform::WORDPRESS_WOOCOMMERCE->value,
                'platform_host' => $request->provider_platform_host,
                'api_key' => $request->provider_api_key,
                'api_secret' => $request->provider_api_secret,
                'status' => (int) $request->provider_status,
                'description' => $request->provider_description,
            ]);
        }

        if ($request->kyc_credential) {
            $kycs = $request->kyc_credential;

            foreach ($kycs as $id => $kyc) {
                if (is_array($kyc)) {
                    foreach ($kyc as $key => $value) {
                        if (is_file($value)) {
                            $kycs[$id][$key] = self::imageUploadTrait($value);
                        }
                    }
                }
            }

            foreach ($request->kyc_ids as $id) {
                $kyc = Kyc::find($id);

                UserKyc::create([
                    'user_id' => $user->id,
                    'kyc_id' => $kyc->id,
                    'type' => $kyc->name,
                    'data' => $kycs[$id],
                    'is_valid' => true,
                    'status' => 'pending',
                ]);
            }
        }

        if (setting('referral_signup_bonus', 'permission') && (float) setting('signup_bonus', 'fee') > 0) {
            $signupBonus = (float) setting('signup_bonus', 'fee');
            $user->increment('balance', $signupBonus);
            (new Txn)->new($signupBonus, 0, $signupBonus, 'system', 'Signup Bonus', TxnType::SignupBonus, TxnStatus::Success, null, null, $user->id);
        }

        notify()->success(__('Customer added successfully!'), 'Success');

        return to_route('admin.user.edit', $user->id);

    }

    /**
     * @return RedirectResponse
     */
    public function update($id, Request $request)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'country' => ['nullable'],
            'gender' => ['required_if:user_type,merchant', 'nullable', 'in:Male,Female,Other'],
            'date_of_birth' => ['required_if:user_type,merchant', 'nullable', 'date'],
            'city' => ['required_if:user_type,merchant', 'nullable', 'string', 'max:255'],
            'zip_code' => ['required_if:user_type,merchant', 'nullable', 'string', 'max:50'],
            'address' => ['required_if:user_type,merchant', 'nullable', 'string', 'max:1000'],
            'user_type' => ['required', 'in:merchant,buyer'],
            'public_key ' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $data = $validator->validated();
        $data['date_of_birth'] = $request->date('date_of_birth');

        $user->update($data);
        notify()->success(__('User Info Updated Successfully'), 'Success');

        return back();
    }

    /**
     * @return RedirectResponse
     */
    public function passwordUpdate($id, Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'new_password' => ['required'],
            'new_confirm_password' => ['same:new_password'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $password = $validator->validated();

        User::find($id)->update([
            'password' => Hash::make($password['new_password']),
        ]);
        notify()->success(__('User Password Updated Successfully'), 'Success');

        return back();
    }

    /**
     * @return RedirectResponse|void
     */
    public function balanceUpdate($id, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'amount' => ['required'],
            'type' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');
            return back();
        }

        try {

            $amount = $request->amount;
            $type = $request->type;
            $wallet = 'balance';

            $user = User::find($id);
            $adminUser = Auth::user();

            if ($type == 'add') {

                $user->is_seller ? $user->balance += $amount : $user->balance += $amount;
                $user->save();

                (new Txn)->new($amount, 0, $amount, 'system', 'Money added in ' . ucwords($wallet) . ' Wallet from System', TxnType::Deposit, TxnStatus::Success, setting('site_currency'), null, $id, $adminUser->id, 'Admin');

                $status = 'Success';
                $message = __('Balance added successfully!');

            } elseif ($type == 'subtract') {

                $user->is_seller ? $user->balance -= $amount : $user->balance -= $amount;
                $user->save();

                (new Txn)->new($amount, 0, $amount, 'system', 'Money subtract in ' . ucwords($wallet) . ' Wallet from System', TxnType::Subtract, TxnStatus::Success, setting('site_currency'), null, $id, $adminUser->id, 'Admin');
                $status = 'Success';
                $message = __('Balance subtracted successfully!');
            }

            notify()->success($message, $status);

            return back();

        } catch (Exception $e) {
            $status = 'warning';
            $message = __('something is wrong');
            $code = 503;
        }

    }

    /**
     * @return Application|Factory|View
     */
    public function mailSendAll()
    {
        return view('backend.user.mail_send_all');
    }

    /**
     * @return RedirectResponse
     */
    public function mailSend(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'subject' => ['required'],
            'message' => ['required'],
            'user_type' => ['required_without:id', 'in:all,buyer,merchant'],
            'id' => ['required_unless:user_type,all', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        try {

            $input = [
                'subject' => $request->subject,
                'message' => $request->message,
            ];

            $shortcodes = [
                '[[subject]]' => $input['subject'],
                '[[message]]' => $input['message'],
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_url]]' => route('home'),
            ];

            if (isset($request->id)) {
                $user = User::find($request->id);

                $shortcodes = array_merge($shortcodes, ['[[full_name]]' => $user->full_name]);

                $this->sendNotify($user->email, 'user_mail', 'User', $shortcodes, $user->phone, $user->id);

            } else {
                $users = User::where('status', 1)
                    ->when($request->user_type != 'all', function ($query) use ($request) {
                        $query->where('user_type', $request->user_type);
                    })->get();

                foreach ($users as $user) {
                    $shortcodes = array_merge($shortcodes, ['[[full_name]]' => $user->full_name]);

                    $this->sendNotify($user->email, 'user_mail', 'User', $shortcodes, $user->phone, $user->id);
                }

            }
            $status = 'Success';
            $message = __('Mail Send Successfully');

        } catch (Exception $e) {

            $status = 'warning';
            $message = __('Sorry, something is wrong');
        }

        notify()->$status($message, $status);

        return back();
    }

    /**
     * @return RedirectResponse
     */
    public function userLogin($id)
    {
        $user = User::findOrFail($id);
        Auth::guard('web')->loginUsingId($user->id);

        return redirect()->away(frontendPanelUrl('dashboard', null, $user->is_seller));
    }

    public function destroy($id)
    {

        try {

            $user = User::find($id);
            $user->kycs()->delete();
            $user->transaction()->delete();
            $user->ticket()->delete();
            $user->activities()->delete();
            $user->messages()->delete();
            $user->notifications()->delete();
            $user->withdrawAccounts()->delete();
            $user->listings()->delete();
            $user->delete();

            notify()->success(__('User deleted successfully'), 'Success');

            $redirectRoute = $user->user_type === 'merchant' ? 'admin.user.merchants.all' : 'admin.user.buyers.all';

            return to_route($redirectRoute);

        } catch (Throwable $th) {
            notify()->error(__('Sorry, something went wrong!'), 'Error');
            return back();
        }

    }

    public function popularToggle($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_popular' => !$user->is_popular]);
        notify()->success(__('User popular status updated successfully!'));

        return back();
    }

    public function disabled(Request $request, $type = null)
    {
        $search = $request->query('query') ?? null;

        $users = User::disabled()
            ->when(filled(request('email_status')), function ($query) {
                if (request('email_status')) {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            })
            ->when(filled($type), function ($query) use ($type) {
                $query->where('user_type', $type);
            })
            ->when(filled(request('kyc_status')), function ($query) {
                $query->where('kyc', request('kyc_status'));
            })
            ->where('status', '0')
            ->when(filled(request('sort_field')), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->when(!request()->has('sort_field'), function ($query) {
                $query->latest();
            })
            ->search($search)
            ->paginate();

        $title = __('Disabled :type', ['type' => ucfirst($type)]);

        return view('backend.user.index', compact('users', 'title'));
    }

    public function __disabled(Request $request)
    {
        $userType = str(url()->current())->after('disabled/')->value();

        return $this->disabled($request, $userType);
    }

    public function __closed(Request $request)
    {
        $userType = str(url()->current())->after('closed/')->value();

        return $this->closed($request, $userType);
    }

    public function closed(Request $request, $type = null)
    {
        $search = $request->query('query') ?? null;

        $users = User::closed()
            ->when(filled(request('email_status')), function ($query) {
                if (request('email_status')) {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            })
            ->when(filled($type), function ($query) use ($type) {
                $query->where('user_type', $type);
            })
            ->when(filled(request('kyc_status')), function ($query) {
                $query->where('kyc', request('kyc_status'));
            })
            ->where('status', '2')
            ->when(filled(request('sort_field')), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->when(!request()->has('sort_field'), function ($query) {
                $query->latest();
            })
            ->search($search)
            ->paginate();
        $title = __('Closed ', ['type' => ucfirst($type)]);

        return view('backend.user.index', compact('users', 'title'));
    }
}
