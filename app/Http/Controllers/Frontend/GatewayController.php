<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\GatewayType;
use App\Http\Controllers\Controller;
use App\Models\DepositMethod;
use App\Traits\NotifyTrait;
use App\Traits\Payment;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    use NotifyTrait;
    use Payment;

    public function gateway(Request $request, $code)
    {

        if ($code == 'undefined') {
            abort(404, 'No Gateway Found');
        }

        $gateway = DepositMethod::code($code)->first();
        if (in_array($code, ['topup', 'balance'])) {
            $gatewayPayAmount['payAmount'] = $request->get('amount');
            $gatewayPayAmount['charge'] = 0;
            $gatewayPayAmount['finalAmount'] = $request->get('amount');
            $gatewayPayAmount['gatewayInfo'] = null;
            $gateway = [];
            $gateway['gatewayPayAmount'] = $gatewayPayAmount;
            $gateway['currency'] = setting('currency_symbol', 'global');

            return array_merge($gateway, ['gatewayPayAmount' => $gatewayPayAmount]);

        }

        if ($request->get('amount') == 0) {
            $gatewayPayAmount['payAmount'] = $gatewayPayAmount['charge'] = $gatewayPayAmount['finalAmount'] = 0;
        } else {
            $__gatewayPayAmount = gatewayPayAmount($code, $request->get('amount'));
            $gatewayPayAmount = [];
            $gatewayPayAmount['payAmount'] = number_format($__gatewayPayAmount[0], 2);
            $gatewayPayAmount['charge'] = number_format($__gatewayPayAmount[1], 2);
            $gatewayPayAmount['finalAmount'] = $__gatewayPayAmount[2];
        }

        if ($gateway->type == GatewayType::Manual->value) {
            $fieldOptions = $gateway->field_options;
            $paymentDetails = $gateway->payment_details;

            $viewFile = $request->filled('fromAccxoneCheckout') ? 'frontend::checkout.manual' : 'frontend::gateway.include.manual';

            $gateway = array_merge($gateway->toArray(), ['gatewayPayAmount' => $gatewayPayAmount], ['credentials' => view($viewFile, compact('fieldOptions', 'paymentDetails'))->render()]);

            return array_merge($gateway, ['gatewayPayAmount' => $gatewayPayAmount]);
        }
        $gatewayCurrency = is_custom_rate($gateway->gateway->gateway_code) ?? $gateway->currency;
        $gateway['currency'] = $gatewayCurrency;
        $gateway['gatewayPayAmount'] = $gatewayPayAmount;

        return array_merge($gateway?->toArray(), ['gatewayPayAmount' => $gatewayPayAmount]);

    }

    public function gatewayList()
    {
        $gateways = DepositMethod::where('status', 1)->get();

        return view('frontend::gateway.include.__list', compact('gateways'));
    }
}
