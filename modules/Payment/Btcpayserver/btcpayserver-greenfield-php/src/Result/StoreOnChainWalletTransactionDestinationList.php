<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class StoreOnChainWalletTransactionDestinationList extends AbstractListResult
{
    /**
     * @return StoreOnChainWalletTransactionDestination[]
     */
    public function all(): array
    {
        $destinations = [];
        foreach ($this->getData() as $destination) {
            $destinations[] = new StoreOnChainWalletTransactionDestination($destination);
        }

        return $destinations;
    }
}
