<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class AddressList extends AbstractListResult
{
    /**
     * @return Address[]
     */
    public function all(): array
    {
        $r = [];
        foreach ($this->getData()['addresses'] as $addressData) {
            $r[] = new Address($addressData);
        }

        return $r;
    }
}
