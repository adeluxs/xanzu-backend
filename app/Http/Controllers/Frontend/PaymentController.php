<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\PlanHistoryStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\PlanHistory;
use App\Models\Transaction;
use App\Models\WithdrawAccount;
use App\Models\WithdrawalSchedule;
use App\Models\WithdrawMethod;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use App\Traits\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ImageUpload;
    use NotifyTrait;
    use Payment;

    public function index()
    {
        $user = auth()->user();
        if (! setting('kyc_'.$user->user_type.'_withdraw', 'kyc') && ($user->kyc == 0 || $user->kyc == 2)) {
            notify()->error(__('Please verify your KYC.'), 'Error');

            return to_buyerSellerRoute('dashboard');
        } elseif (! setting($user->user_type.'_withdraw', 'permission') || ! $user->withdraw_status) {
            notify()->error(__('Withdraw currently unavailable'), 'Error');

            return to_buyerSellerRoute('dashboard');
        }

        if (request()->route()->getName() == 'user.payment.index' && site_theme() == 'default') {
            return to_buyerSellerRoute('payment.withdraw.index');
        }

        $accounts = WithdrawAccount::with('method')->where('user_id', auth()->id())->get();
        $withdrawMethods = WithdrawMethod::where('status', true)->get();

        $from_date = trim(@explode('-', request('daterange'))[0]);
        $to_date = trim(@explode('-', request('daterange'))[1]);

        $withdrawHistory = Transaction::where('user_id', auth()->id())
            ->whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto])
            ->search(request('trx'))
            ->when(request('daterange'), function ($query) use ($from_date, $to_date) {
                $query->whereDate('created_at', '>=', Date::parse($from_date)->format('Y-m-d'));
                $query->whereDate('created_at', '<=', Date::parse($to_date)->format('Y-m-d'));
            })
            ->latest()
            ->paginate(request()->integer('limit', 15))
            ->withQueryString();

        return view('frontend::payment.index', compact('accounts', 'withdrawMethods', 'withdrawHistory'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (! setting('kyc_'.$user->user_type.'_withdraw', 'kyc') && ($user->kyc == 0 || $user->kyc == 2)) {
            notify()->error(__('Please verify your KYC.'), 'Error');

            return to_buyerSellerRoute('dashboard');
        } elseif (! setting($user->user_type.'_withdraw', 'permission') || ! $user->withdraw_status) {
            notify()->error(__('Withdraw currently unavailable'), 'Error');

            return to_buyerSellerRoute('dashboard');
        }

        $validator = Validator::make($request->all(), [
            'withdraw_method_id' => ['required'],
            'method_name' => ['required'],
            'credentials' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $input = $request->all();

        $credentials = $input['credentials'];
        foreach ($credentials as $key => $value) {

            if (isset($value['value']) && is_file($value['value'])) {
                $credentials[$key]['value'] = self::imageUploadTrait($value['value']);
            }
        }

        $data = [
            'user_id' => auth()->id(),
            'withdraw_method_id' => $input['withdraw_method_id'],
            'method_name' => $input['method_name'],
            'credentials' => json_encode($credentials),
        ];

        WithdrawAccount::create($data);

        notify()->success(__('Withdraw cccount created successfully'), 'success');

        return back();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function account()
    {
        $withdrawMethods = WithdrawMethod::where('status', true)->get();
        $accounts = WithdrawAccount::with('method')->where('user_id', auth()->id())->get();

        return view('frontend::payment.account.index', compact('withdrawMethods', 'accounts'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $withdrawMethods = WithdrawMethod::all();
        $withdrawAccount = WithdrawAccount::query()->where('user_id', auth()->id())->findOrFail(decrypt($id));

        return response()->json([
            'html' => view('frontend::payment.account.edit', compact('withdrawMethods', 'withdrawAccount'))->render(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'method_name' => ['required'],
            'credentials' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $input = $request->all();

        $withdrawAccount = WithdrawAccount::query()->where('user_id', auth()->id())->findOrFail(decrypt($id));

        $oldCredentials = json_decode($withdrawAccount->credentials, true);

        $credentials = $input['credentials'];

        foreach ($credentials as $key => $value) {

            if (! isset($value['value'])) {
                $credentials[$key]['value'] = data_get($oldCredentials[$key], 'value');
            }

            if (isset($value['value']) && is_file($value['value'])) {
                $credentials[$key]['value'] = self::imageUploadTrait($value['value'], data_get($oldCredentials[$key], 'value'));
            }
        }

        $data = [
            'user_id' => auth()->id(),
            'method_name' => $input['method_name'],
            'credentials' => json_encode($credentials),
        ];

        $withdrawAccount->update($data);
        notify()->success(__('Withdraw account updated successfully'), 'success');

        return to_buyerSellerRoute('payment.withdraw.account.index');
    }

    public function delete($id)
    {
        WithdrawAccount::query()->where('user_id', auth()->id())->whereKey(decrypt($id))->delete();

        notify()->success(__('Withdraw account deleted successfully!'), 'Success');

        return back();
    }

    /**
     * @return string
     */
    public function withdrawMethod($id)
    {
        $withdrawMethod = WithdrawMethod::find($id);

        if ($withdrawMethod) {
            return view('frontend::payment.include.__account', compact('withdrawMethod'))->render();
        }

        return '';
    }

    /**
     * @return array
     */
    public function details($accountId, int $amount = 0)
    {
        $withdrawAccount = WithdrawAccount::with('method')->where('user_id', auth()->id())->find($accountId);

        $credentials = json_decode($withdrawAccount?->credentials, true);

        $currency = setting('site_currency', 'global');
        $method = $withdrawAccount->method;
        $charge = $method->charge;
        $name = $withdrawAccount->method_name;
        $processingTime = (int) $method->required_time > 0 ? $withdrawAccount->method->required_time.$withdrawAccount->method->required_time_format : 'This Is Automatic Method';

        if ($withdrawAccount->method->charge_type != 'fixed') {
            $charge = ($charge / 100) * $amount;
        }

        $info = [
            'name' => $name,
            'charge' => $charge,
            'charge_amount' => $method->charge,
            'charge_type' => $withdrawAccount->method->charge_type,
            'range' => 'Minimum '.$method->min_withdraw.' '.$currency.' and '.'Maximum '.$method->max_withdraw.' '.$currency,
            'processing_time' => $processingTime,
            'rate' => $method->rate,
            'pay_currency' => $method->currency,
            'logo' => asset($withdrawAccount->method->icon),
            'pay_amount' => ($amount) * $method->rate,
        ];
        $conversionRate = $method->currency != $currency ? $method->rate.' '.$method->currency : null;
        $html = view('frontend::payment.include.__details', compact('credentials', 'name', 'charge', 'conversionRate', 'amount'))->render();

        return [
            'html' => $html,
            'info' => $info,
        ];
    }

    /**
     * @return string
     */
    public function withdrawNow(Request $request)
    {
        $user = auth()->user();
        if (! setting($user->user_type.'_withdraw', 'permission') || ! Auth::user()->withdraw_status) {
            notify()->error(__('Withdraw currently unavailable'), 'Error');

            return to_buyerSellerRoute('dashboard');
        } elseif (! setting('kyc_withdraw', 'kyc') && (auth()->user()->kyc == 0 || auth()->user()->kyc == 2)) {
            notify()->error(__('Please verify your KYC.'), 'Error');

            return to_buyerSellerRoute('dashboard');
        }

        $withdrawOffDays = WithdrawalSchedule::where('status', 0)->pluck('name')->toArray();
        $date = Date::now();
        $today = $date->format('l');

        if (in_array($today, $withdrawOffDays)) {
            notify()->error(__('Today is the off day of withdraw'), 'Error');

            return back();
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'regex:/^[0-9]+(\.[0-9][0-9]?)?$/'],
            'withdraw_account' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        // daily limit
        $todayTransaction = Transaction::where('user_id', auth()->id())->whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto])->whereNotIn('status', [TxnStatus::Failed->value, TxnStatus::Cancelled->value])->whereDate('created_at', Date::today())->count();
        $dayLimit = (float) Setting('withdraw_day_limit', 'fee');
        if ($dayLimit > 0 && $todayTransaction >= $dayLimit) {
            notify()->error(__('Today Withdraw limit has been reached'), 'Error');

            return back();
        }

        // Check limit for paid user and free user
        $user = Auth::user();
        $planWithdrawLimit = PlanHistory::where('user_id', $user->id)->where('status', PlanHistoryStatus::ACTIVE)->sum('withdraw_limit');

        // Handle paid user case

        $totalWithdraw = Transaction::where('user_id', $user->id)->whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto])->where('status', TxnStatus::Success)->sum('amount');

        if ($planWithdrawLimit > 0 && ($totalWithdraw + (float) $request->amount) > $planWithdrawLimit) {
            notify()->error(__('You have reached your withdraw limit!'), 'Error');

            return back();
        }

        $input = $request->all();
        $amount = (float) $input['amount'];

        $withdrawAccount = WithdrawAccount::with('method.gateway')->where('user_id', $user->id)->find($input['withdraw_account']);
        if (! $withdrawAccount || ! $withdrawAccount->method?->status) {
            notify()->error(__('Withdraw account or method is unavailable'), 'Error');
            return back();
        }
        $withdrawMethod = $withdrawAccount->method;

        if ($amount < $withdrawMethod->min_withdraw || ((float) $withdrawMethod->max_withdraw > 0 && $amount > $withdrawMethod->max_withdraw)) {
            $currencySymbol = setting('currency_symbol', 'global');
            $message = 'Please Withdraw the Amount within the range '.$currencySymbol.$withdrawMethod->min_withdraw.' to '.$currencySymbol.$withdrawMethod->max_withdraw;
            notify()->error($message, 'Error');

            return back();
        }

        $charge = $withdrawMethod->charge_type == 'percentage' ? (($withdrawMethod->charge / 100) * $amount) : $withdrawMethod->charge;
        $totalAmount = $amount + (float) $charge;

        if ($user->balance < $totalAmount) {
            notify()->error(__('Insufficient Balance'), 'Error');

            return back();
        }

        $user->decrement('balance', $totalAmount);

        if ((float) $withdrawMethod->rate <= 0) {
            notify()->error(__('The selected withdraw method has an invalid rate.'), 'Error');
            return back();
        }

        $payAmount = $amount * $withdrawMethod->rate;

        $type = $withdrawMethod->type == 'auto' ? TxnType::WithdrawAuto : TxnType::Withdraw;

        $txnInfo = (new Txn)->new(
            $input['amount'],
            $charge,
            $totalAmount,
            $withdrawMethod->name,
            'Withdraw With '.$withdrawAccount->method_name,
            $type,
            TxnStatus::Pending,
            $withdrawMethod->currency,
            $payAmount,
            $user->id,
            null,
            'User',
            json_decode($withdrawAccount->credentials, true)
        );

        if ($withdrawMethod->type == 'auto') {
            $gatewayCode = $withdrawMethod->gateway->gateway_code;

            notify()->success(__('Withdraw request has been sent successfully!'));

            return self::withdrawAutoGateway($gatewayCode, $txnInfo);
        }

        $symbol = setting('currency_symbol', 'global');

        $shortcodes = [
            '[[full_name]]' => $txnInfo->user->full_name,
            '[[txn]]' => $txnInfo->tnx,
            '[[method_name]]' => $withdrawMethod->name,
            '[[withdraw_amount]]' => $txnInfo->amount.setting('site_currency', 'global'),
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
        ];

        $this->sendNotify(
            setting('site_email', 'global'),
            'withdraw_request',
            'Admin',
            $shortcodes,
            $user->phone,
            $user->id,
            route('admin.withdraw.pending')
        );

        notify()->success(__('Withdraw request has been sent successfully!'));

        return to_buyerSellerRoute('transactions');
    }

    /**
     * @return Application|Factory|View
     */
    public function withdraw()
    {

        if (! setting('kyc_withdraw') && (auth()->user()->kyc == 0 || auth()->user()->kyc == 2)) {
            notify()->error(__('Please verify your KYC.'), 'Error');

            return to_buyerSellerRoute('dashboard');
        }

        $accounts = WithdrawAccount::with('method')->where('user_id', \Auth::id())->get();
        $accounts = $accounts->reject(function ($value, $key) {
            return ! $value->method->status;
        });

        return view('frontend::payment.now', compact('accounts'));
    }

    public function withdrawLog()
    {
        $from_date = trim(@explode('-', request('daterange'))[0]);
        $to_date = trim(@explode('-', request('daterange'))[1]);

        $withdraws = Transaction::where('user_id', auth()->id())
            ->whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto])
            ->search(request('trx'))
            ->when(request('daterange'), function ($query) use ($from_date, $to_date) {
                $query->whereDate('created_at', '>=', Date::parse($from_date)->format('Y-m-d'));
                $query->whereDate('created_at', '<=', Date::parse($to_date)->format('Y-m-d'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(request('limit', 15))
            ->withQueryString();

        return view('frontend::payment.log', compact('withdraws'));
    }
}
