<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class StoreOnChainWalletTransactionList extends AbstractListResult
{
    /**
     * @return StoreOnChainWalletTransaction[]
     */
    public function all(): array
    {
        $storeWalletTransactions = [];
        foreach ($this->getData() as $storeWalletTransaction) {
            $storeWalletTransactions[] = new StoreOnChainWalletTransaction($storeWalletTransaction);
        }

        return $storeWalletTransactions;
    }
}
