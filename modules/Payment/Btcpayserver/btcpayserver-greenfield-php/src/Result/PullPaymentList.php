<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class PullPaymentList extends AbstractListResult
{
    /**
     * @return PullPayment[]
     */
    public function all(): array
    {
        $pullPayments = [];
        foreach ($this->getData() as $pullPaymentData) {
            $pullPayments[] = new PullPayment($pullPaymentData);
        }

        return $pullPayments;
    }
}
