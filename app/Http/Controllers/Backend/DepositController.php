<?php

namespace App\Http\Controllers\Backend;

use App\Enums\GatewayType;
use App\Enums\OrderStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\DepositMethod;
use App\Models\Gateway;
use App\Models\LevelReferral;
use App\Models\Transaction;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use App\Traits\PlanTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

class DepositController extends Controller
{
    use ImageUpload;
    use NotifyTrait;
    use PlanTrait;

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:deposit-list|deposit-action', ['only' => ['pending', 'history']]);
        $this->middleware('permission:deposit-action', ['only' => ['depositAction', 'actionNow']]);
    }

    // -------------------------------------------  Deposit method start ---------------------------------------------------------------

    public function methodList($type)
    {
        $button = [
            'name' => __('ADD NEW'),
            'icon' => 'plus',
            'route' => route('admin.deposit.method.create', $type),
        ];

        $depositMethods = DepositMethod::where('type', $type)->get();

        return view('backend.deposit.method_list', compact('depositMethods', 'button', 'type'));
    }

    public function createMethod($type)
    {
        $gateways = Gateway::where('status', true)->get();

        return view('backend.deposit.create_method', compact('type', 'gateways'));
    }

    public function methodStore(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'logo' => ['required_if:type,==,manual'],
            'name' => ['required'],
            'gateway_id' => ['required_if:type,==,auto'],
            'method_code' => ['required_if:type,==,manual'],
            'currency' => ['required'],
            'currency_symbol' => ['required'],
            'charge' => ['required'],
            'charge_type' => ['required'],
            'rate' => ['required'],
            'minimum_deposit' => ['required'],
            'maximum_deposit' => ['required'],
            'status' => ['required'],
            'field_options' => ['required_if:type,==,manual'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        if (isset($input['gateway_id'])) {
            $gateway = Gateway::find($input['gateway_id']);
            $methodCode = $gateway->gateway_code.'-'.strtolower($input['currency']);
        }

        $data = [
            'logo' => isset($input['logo']) ? self::imageUploadTrait($input['logo']) : null,
            'name' => $input['name'],
            'type' => $input['type'],
            'gateway_id' => $input['gateway_id'] ?? null,
            'gateway_code' => $input['method_code'] ?? $methodCode,
            'currency' => $input['currency'],
            'currency_symbol' => $input['currency_symbol'],
            'charge' => $input['charge'],
            'charge_type' => $input['charge_type'],
            'rate' => $input['rate'],
            'minimum_deposit' => $input['minimum_deposit'],
            'maximum_deposit' => $input['maximum_deposit'],
            'status' => $input['status'],
            'field_options' => ($input['field_options']),
            'payment_details' => isset($input['payment_details']) ? Purifier::clean(htmlspecialchars_decode($input['payment_details'])) : null,
        ];

        $depositMethod = DepositMethod::create($data);
        notify()->success($depositMethod->name.' '.__(' Method Created'));

        return to_route('admin.deposit.method.list', $depositMethod->type);
    }

    public function methodEdit($type)
    {
        $gateways = Gateway::where('status', true)->get();
        $method = DepositMethod::find(\request('id'));
        $supported_currencies = Gateway::find($method->gateway_id)->supported_currencies ?? [];

        return view('backend.deposit.edit_method', compact('method', 'type', 'gateways', 'supported_currencies'));
    }

    public function methodUpdate($id, Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => ['required'],
            'gateway_id' => ['required_if:type,==,auto'],
            'currency' => ['required'],
            'currency_symbol' => ['required'],
            'charge' => ['required'],
            'charge_type' => ['required'],
            'rate' => ['required'],
            'minimum_deposit' => ['required'],
            'maximum_deposit' => ['required'],
            'status' => ['required'],
            'field_options' => ['required_if:type,==,manual'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $depositMethod = DepositMethod::find($id);

        $user = auth()->user();
        if ($depositMethod->type == GatewayType::Automatic) {
            if (! $user->can('automatic-gateway-manage')) {
                return to_route('admin.deposit.method.list', $depositMethod->type);
            }
        } else {
            if (! $user->can('manual-gateway-manage')) {
                return to_route('admin.deposit.method.list', $depositMethod->type);
            }
        }
        $data = [
            'name' => $input['name'],
            'type' => $input['type'],
            'gateway_id' => $input['gateway_id'] ?? null,
            'currency' => $input['currency'],
            'currency_symbol' => $input['currency_symbol'],
            'charge' => $input['charge'],
            'charge_type' => $input['charge_type'],
            'rate' => $input['rate'],
            'minimum_deposit' => $input['minimum_deposit'],
            'maximum_deposit' => $input['maximum_deposit'],
            'status' => $input['status'],
            'field_options' => ($input['field_options'] ?? null),
            'payment_details' => isset($input['payment_details']) ? Purifier::clean(htmlspecialchars_decode($input['payment_details'])) : null,
        ];

        if ($request->hasFile('logo')) {
            $logo = self::imageUploadTrait($input['logo'], $depositMethod->logo);
            $data = array_merge($data, ['logo' => $logo]);
        }

        $depositMethod->update($data);
        notify()->success($depositMethod->name.' '.__(' Method Updated'));

        return to_route('admin.deposit.method.list', $depositMethod->type);
    }

    // -------------------------------------------  Deposit method end ---------------------------------------------------------------

    public function pending(Request $request)
    {

        $perPage = $request->perPage ?? 15;
        $order = $request->order ?? 'asc';
        $search = $request->search ?? null;
        $deposits = Transaction::with('user')
            ->pendingManualTxn()
            ->search($search)
            ->when(in_array(request('sort_field'), ['created_at', 'amount', 'charge', 'method', 'status', 'tnx']), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->when(request('sort_field') == 'user', function ($query) {
                $query->whereHas('user', function ($userQuery) {
                    $userQuery->orderBy('username', request('sort_dir'));
                });
            })
            ->when(! request()->has('sort_field'), function ($query) {
                $query->latest();
            })
            ->paginate($perPage);

        return view('backend.deposit.manual', compact('deposits'));
    }

    public function history(Request $request)
    {

        $perPage = $request->perPage ?? 15;
        $order = $request->order ?? 'asc';
        $search = $request->search ?? null;
        $status = $request->status ?? 'all';
        $deposits = Transaction::with('user')
            ->whereIn('type', [TxnType::ManualDeposit->value, TxnType::Deposit->value, TxnType::ProductOrder->value, TxnType::BnplInstallment->value])
            ->search($search)
            ->when(in_array(request('sort_field'), ['created_at', 'amount', 'charge', 'method', 'status', 'tnx']), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->when(request('sort_field') == 'user', function ($query) {
                $query->whereHas('user', function ($userQuery) {
                    $userQuery->orderBy('username', request('sort_dir'));
                });
            })
            ->when(! request()->has('sort_field'), function ($query) {
                $query->latest();
            })
            ->status($status)
            ->paginate($perPage);

        return view('backend.deposit.history', compact('deposits'));
    }

    public function depositAction($id)
    {

        $data = Transaction::find($id);

        return view('backend.deposit.include.__deposit_action', compact('data', 'id'))->render();
    }

    public function actionNow(Request $request)
    {
        $input = $request->all();
        $id = $input['id'];
        $approvalCause = $input['message'];
        $transaction = Transaction::find($id);

        if (isset($input['approve'])) {

            if ($transaction->type == TxnType::ProductOrder) {
                $response = orderService()->orderPaymentSuccess($transaction->order);
                if (! $response) {
                    return back();
                }
            } elseif ($transaction->type == TxnType::Topup) {
                $transaction->user->increment('balance', $transaction->final_amount - $transaction->charge);

                $level = LevelReferral::where('type', 'topup')->max('the_order');
                creditReferralBonus($transaction->user, 'topup', $transaction->amount, $level);
            } elseif ($transaction->type == TxnType::BnplInstallment) {
                orderService()->markBnplInstallmentTransactionPaid($transaction);

            }

            (new Txn)->update($transaction->tnx, TxnStatus::Success, $transaction->user_id, $approvalCause);

            notify()->success(__('Request approved successfully!'));
        } elseif (isset($input['reject'])) {
            (new Txn)->update($transaction->tnx, TxnStatus::Failed, $transaction->user_id, $approvalCause);

            if ($transaction->type == TxnType::BnplInstallment) {
                orderService()->markBnplInstallmentTransactionRejected($transaction);
            }

            // order cancel (only for product order payment request)
            if ($transaction->type == TxnType::ProductOrder && $transaction->order) {
                $transaction->order->update(['payment_status' => TxnStatus::Failed->value, 'status' => OrderStatus::Cancelled->value]);
            }

            notify()->success(__('Request rejected successfully!'));
        }

        $shortcodes = [
            '[[full_name]]' => $transaction->user->full_name,
            '[[txn]]' => $transaction->tnx,
            '[[gateway_name]]' => $transaction->method,
            '[[deposit_amount]]' => $transaction->amount,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[message]]' => $transaction->approval_cause,
            '[[status]]' => isset($input['approve']) ? 'Approved' : 'Rejected',
        ];

        $this->sendNotify(
            $transaction->user->email,
            'user_manual_payment_request',
            'User',
            $shortcodes,
            $transaction->user->phone,
            $transaction->user->id,
            frontendPanelUrl('transactions', null, false)
        );

        return back();
    }
}
