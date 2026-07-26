<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\DepositMethod;
use App\Models\PlanHistory;
use App\Models\SubscriptionPlan;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use App\Traits\PlanTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SubscriptionController extends GatewayController
{
    use ImageUpload;
    use NotifyTrait;
    use PlanTrait;

    public function index()
    {
        $plans = SubscriptionPlan::latest()->paginate();

        return view('frontend::user.subscription.index', compact('plans'));
    }

    public function history()
    {
        $histories = PlanHistory::with('plan')->where('user_id', auth()->id())->latest()->paginate();

        return view('frontend::user.subscription.history', compact('histories'));
    }

    public function purchasePreview(SubscriptionPlan $plan)
    {
        $gateways = DepositMethod::where('status', 1)->get();

        return view('frontend::user.subscription.purchase_now', compact('plan', 'gateways'));
    }

    public function subscriptionNow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'method' => ['required', 'in:balance,gateway,topup'],
            'plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back();
        }

        try {

            DB::beginTransaction();

            $user = $request->user();
            $plan = SubscriptionPlan::findOrFail($request->plan_id);
            $planPrice = $plan->price;
            $payMethod = $request->get('method', 'balance');

            if ($payMethod == 'balance' && $user->balance < $planPrice) {
                notify()->error(__('Insufficient Balance.'));

                return back();
            }

            $error = match (true) {
                $payMethod == 'topup' && $user->balance < $planPrice => __('Insufficient Balance.'),
                $payMethod == 'balance' && $user->balance < $planPrice => __('Insufficient Balance.'),
                default => null
            };

            if ($error !== null) {
                notify()->error($error);

                return back();
            }

            if ($payMethod == 'topup') {
                $user->decrement('balance', $planPrice);
            } elseif ($payMethod == 'balance') {
                $user->decrement('balance', $planPrice);
            } else {

                [$payAmount, $charge, $finalAmount, $gatewayInfo] = gatewayPayAmount($request->input('gateway_code'), $planPrice);
                $manualData = processManualDepositData($request);

                $txnInfo = (new Txn)->new($planPrice, $charge, $finalAmount, $gatewayInfo->name, $plan->name . ' Purchased', TxnType::PlanPurchased, TxnStatus::Pending, $gatewayInfo->currency, $payAmount, $user->id, null, 'User', $manualData ?? [], planId: $plan->id);
                DB::commit();

                return self::depositAutoGateway($request->get('gateway_code'), $txnInfo);
            }

            // Execute plan purchase process
            $txnInfo = (new Txn)->new($planPrice, 0, $planPrice, 'system', $plan->name . ' Purchased', TxnType::PlanPurchased, TxnStatus::Success, null, null, $user->id, planId: $plan->id);
            $this->executePlanPurchaseProcess($user, $plan, $txnInfo);

            $shortcodes = [
                '[[full_name]]' => auth()->user()->full_name,
                '[[username]]' => auth()->user()->username,
                '[[plan_name]]' => $plan->name,
                '[[amount]]' => $planPrice,
                '[[subscribed_at]]' => $txnInfo->created_at,
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_url]]' => route('home'),
            ];

            $this->sendNotify($txnInfo->user->email, 'plan_subscribed', 'Admin', $shortcodes, $txnInfo->user->phone, $txnInfo->user_id, route('admin.transactions'));
            $this->sendNotify($txnInfo->user->email, 'plan_subscribed', 'User', $shortcodes, $txnInfo->user->phone, $txnInfo->user_id, buyerSellerRoute('transactions'));

            DB::commit();

            notify()->success(__('Plan purchased successfully!'));

            return to_buyerSellerRoute('subscriptions.history');
        } catch (Throwable $th) {
            // throw $th;
            DB::rollBack();
            notify()->error($th->getMessage());

            return back();
        }
    }
}
