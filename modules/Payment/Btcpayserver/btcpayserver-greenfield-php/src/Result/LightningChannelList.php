<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class LightningChannelList extends AbstractListResult
{
    /**
     * @return LightningChannel[]
     */
    public function all(): array
    {
        $channels = [];
        foreach ($this->getData() as $channel) {
            $channels[] = new LightningChannel($channel);
        }

        return $channels;
    }
}
