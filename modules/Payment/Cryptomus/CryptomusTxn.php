<?php

namespace Payment\Cryptomus;

use Cryptomus\Api\Client;
use Exception;
use Payment\Transaction\BaseTxn;

class CryptomusTxn extends BaseTxn
{
    protected $payoutKey;

    protected $merchantId;

    protected $paymentKey;

    protected $toAddress;

    protected $toCurrency;

    protected $network;

    public function __construct($txnInfo)
    {
        parent::__construct($txnInfo);
        $credential = gateway_info('cryptomus');
        $this->merchantId = $credential->merchant_id;
        $this->payoutKey = $credential->payout_key;
        $this->paymentKey = $credential->payment_key;
        $this->toCurrency = $credential->to_currency;
        $this->network = $credential->network;

        $fieldData = json_decode($txnInfo->manual_field_data, true);
        $this->toAddress = $fieldData['address']['value'] ?? '';
    }

    public function deposit()
    {
        $payment = Client::payment($this->paymentKey, $this->merchantId);
        $data = [
            'amount' => $this->amount,
            'currency' => 'USD',
            'network' => $this->currency,
            'order_id' => $this->txn,
            'url_return' => route('status.pending', ['reftrn' => encrypt($this->txn)]),
            'url_callback' => route('ipn.cryptomus', ['reftrn' => encrypt($this->txn)]),
            'is_payment_multiple' => false,
            'lifetime' => '7200',
            'to_currency' => $this->toCurrency,
        ];

        try {
            $result = $payment->create($data);
        } catch (\Throwable $th) {
            notify()->error($th->getMessage(), 'Error');

            return back();
        }

        return redirect()->to($result['url']);
    }

    public function withdraw()
    {

        try {
            $payout = Client::payout($this->payoutKey, $this->merchantId);
            $data = [
                'amount' => $this->amount,
                'currency' => 'USD',
                'network' => $this->network,
                'order_id' => $this->txn,
                'address' => $this->toAddress,
                'is_subtract' => '1',
                'url_callback' => route('ipn.cryptomus', ['reftrn' => encrypt($this->txn)]),
            ];

            $payout->create($data);

        } catch (Exception $e) {
            notify()->warning('Not available demo mode', 'warning');

            return redirect()->back();
        }

    }
}
