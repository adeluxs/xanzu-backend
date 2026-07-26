<?php

namespace Payment\Btcpayserver;

use BTCPayServer\Client\Invoice;
use BTCPayServer\Util\PreciseNumber;
use Payment\Transaction\BaseTxn;

class BtcpayserverTxn extends BaseTxn
{
    private $host;

    private $apiKey;

    private $storeId;

    public function __construct($txnInfo)
    {
        parent::__construct($txnInfo);
        $gatewayInfo = gateway_info('btcpayserver');
        $this->host = $gatewayInfo->host;
        $this->apiKey = $gatewayInfo->api_key;
        $this->storeId = $gatewayInfo->store_id;

    }

    public function deposit()
    {

        $amount = $this->amount;
        $currency = $this->currency;
        $orderId = $this->txn;
        $buyerEmail = $this->userEmail;

        // Create a basic invoice.
        try {
            $client = new Invoice($this->host, $this->apiKey);
            $checkoutPage = $client->createInvoice(
                $this->storeId,
                $currency,
                PreciseNumber::parseString($amount),
                $orderId,
                $buyerEmail
            );

            return redirect()->to($checkoutPage['checkoutLink']);

        } catch (\Throwable $e) {
            dd($e->getMessage(), $this->apiKey, $this->host, $this->storeId);
            notify()->error('Something went wrong', 'Error');

            return redirect()->back();
        }
    }
}
