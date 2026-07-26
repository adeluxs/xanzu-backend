<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class StoreOnChainWalletUTXOList extends AbstractListResult
{
    /**
     * @return StoreOnChainWalletUTXO[]
     */
    public function all(): array
    {
        $storeWalletUTXOs = [];
        foreach ($this->getData() as $storeWalletUTXO) {
            $storeWalletUTXOs[] = new StoreOnChainWalletUTXO($storeWalletUTXO);
        }

        return $storeWalletUTXOs;
    }
}
