<?php

namespace Payment\Coingate;

use CoinGate\Client;
use Payment\Transaction\BaseTxn;

class CoingateTxn extends BaseTxn
{
    protected $apiKey;

    public function __construct($txnInfo)
    {
        parent::__construct($txnInfo);
        $this->apiKey = gateway_info('coingate')->api_token;
    }

    public function deposit()
    {
        $client = new Client($this->apiKey, true);

        $params = [
            'order_id' => $this->txn,
            'price_amount' => $this->amount,
            'price_currency' => $this->currency,
            'receive_currency' => 'EUR',
            'callback_url' => route('ipn.coingate', ['reftrn' => encrypt($this->txn)]),
            'cancel_url' => route('status.cancel', ['reftrn' => encrypt($this->txn)]),
            'success_url' => route('status.success', ['reftrn' => encrypt($this->txn)]),
            'title' => $this->siteName,
            'description' => '',
        ];

        $status = $client->order->create($params);

        return redirect()->to($status->payment_url);
    }
}
