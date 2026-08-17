<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Models\DepositMethod;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Txn;

class DepositController extends GatewayController
{
    use ImageUpload;
    use NotifyTrait;

    public function deposit()
    {
        if (! setting('user_deposit', 'permission') || ! Auth::user()->deposit_status) {
            notify()->error(__('Deposit currently unavailable'), 'Error');

            return to_buyerSellerRoute('dashboard');
        } elseif (! setting('kyc_deposit', 'kyc') && (auth()->user()->kyc == 0 || auth()->user()->kyc == 2)) {
            notify()->error(__('Please verify your KYC.'), 'Error');

            return to_buyerSellerRoute('dashboard');
        }

        $gateways = DepositMethod::where('status', 1)->get();

        return view('frontend::deposit.now', compact('gateways'));
    }

    public function store(Request $request)
    {
        // Backward-compatible alias used by the legacy deposit/store route.
        return $this->depositNow($request);
    }

    public function depositNow(Request $request)
    {

        if (! setting('user_deposit', 'permission') || ! Auth::user()->deposit_status) {
            notify()->error(__('Deposit currently unavailable!'), 'Error');

            return to_buyerSellerRoute('dashboard');
        } elseif (! setting('kyc_deposit') && ! auth()->user()->kyc) {
            notify()->error(__('Please verify your KYC.'), 'Error');

            return to_buyerSellerRoute('dashboard');
        }

        $validator = Validator::make($request->all(), [
            'gateway_code' => ['required'],
            'amount' => ['required', 'regex:/^[0-9]+(\.[0-9][0-9]?)?$/'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $input = $request->all();

        $gatewayInfo = DepositMethod::code($input['gateway_code'])->where('status', 1)->first();
        if (! $gatewayInfo) {
            notify()->error(__('Selected deposit method is unavailable.'), 'Error');
            return back();
        }
        $amount = $input['amount'];

        if ($amount < $gatewayInfo->minimum_deposit || $amount > $gatewayInfo->maximum_deposit) {
            $currencySymbol = setting('currency_symbol', 'global');
            $message = 'Please Deposit the Amount within the range '.$currencySymbol.$gatewayInfo->minimum_deposit.' to '.$currencySymbol.$gatewayInfo->maximum_deposit;
            notify()->error($message, 'Error');

            return back();
        }

        $charge = $gatewayInfo->charge_type == 'percentage' ? (($gatewayInfo->charge / 100) * $amount) : $gatewayInfo->charge;
        $finalAmount = (float) $amount + (float) $charge;
        $payAmount = $finalAmount * $gatewayInfo->rate;
        $depositType = TxnType::Deposit;

        if (isset($input['manual_data'])) {

            $depositType = TxnType::ManualDeposit;
            $manualData = $input['manual_data'];

            foreach ($manualData as $key => $value) {

                if ($value instanceof UploadedFile) {
                    $manualData[$key] = self::imageUploadTrait($value);
                }
            }
        }

        $txnInfo = Txn::new($input['amount'], $charge, $finalAmount, $gatewayInfo->gateway_code, 'Deposit With '.$gatewayInfo->name, $depositType, TxnStatus::Pending, $gatewayInfo->currency, $payAmount, auth()->id(), null, 'User', $manualData ?? []);

        return self::depositAutoGateway($gatewayInfo->gateway_code, $txnInfo);
    }
}
