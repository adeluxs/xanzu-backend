<?php

namespace Payment\Coinbase;

use Payment\Transaction\BaseTxn;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackTxn extends BaseTxn
{
    public function __construct($txnInfo)
    {
        parent::__construct($txnInfo);
    }

    public function deposit()
    {
        $data = [
            'amount' => $this->amount * 100,
            'reference' => $this->txn,
            'email' => $this->userEmail,
            'currency' => $this->currency,
            'orderID' => $this->txn,
        ];

        return Paystack::getAuthorizationUrl($data)->redirectNow();
    }
}
