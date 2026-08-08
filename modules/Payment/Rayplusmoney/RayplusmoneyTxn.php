<?php

namespace Payment\Rayplusmoney;

use App\Services\Payments\RayplusmoneyService;
use Payment\Transaction\BaseTxn;

class RayplusmoneyTxn extends BaseTxn
{
    private $transaction;

    public function __construct($txnInfo)
    {
        parent::__construct($txnInfo);
        $this->transaction = $txnInfo;
    }

    public function deposit()
    {
        return app(RayplusmoneyService::class)->createPayin($this->transaction);
    }

    public function withdraw()
    {
        return app(RayplusmoneyService::class)->createPayout($this->transaction);
    }
}
