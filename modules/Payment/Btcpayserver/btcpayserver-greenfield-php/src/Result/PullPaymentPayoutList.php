<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class PullPaymentPayoutList extends AbstractListResult
{
    /**
     * @return PullPaymentPayout[]
     */
    public function all(): array
    {
        $pullPaymentPayouts = [];
        foreach ($this->getData() as $pullPaymentPayoutData) {
            $pullPaymentPayouts[] = new PullPaymentPayout($pullPaymentPayoutData);
        }

        return $pullPaymentPayouts;
    }
}
