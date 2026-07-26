<?php

namespace Payment\Mollie;

use App\Models\Order;
use Mollie\Laravel\Facades\Mollie;
use Payment\Transaction\BaseTxn;

class MollieTxn extends BaseTxn
{
    public function __construct($txnInfo)
    {
        parent::__construct($txnInfo);
    }

    public function deposit()
    {

        $ipnURL = route('ipn.mollie', ['reftrn' => encrypt($this->txn)]);

        if (request()->ip() == '127.0.0.1') {
            $ipnURL = str($ipnURL)->replace('https://gamecon.test', 'https://4984-59-153-103-204.ngrok-free.app/gamecon');
        }

        $data = [
            'amount' => [
                'currency' => $this->currency, // Type of currency you want to send
                'value' => (string) $this->amount . '.00', // You must send the correct number of decimals, thus we enforce the use of strings
            ],
            'description' => $this->siteName,
            'webhookUrl' => $ipnURL,
            'redirectUrl' => route('status.pending', ['reftrn' => encrypt($this->txn)]),
        ];
        $payment = Mollie::api()->payments()->create($data);

        $paymentId = $payment->id;

        $payment = Mollie::api()->payments()->get($payment->id);

        orderService()->setTrnxId(Order::find(session('order_id')), $paymentId);

        // redirect customer to Mollie checkout page
        return redirect($payment->getCheckoutUrl(), 303);
    }
}
