<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class WebhookList extends AbstractListResult
{
    /**
     * @return Webhook[]
     */
    public function all(): array
    {
        $webhooks = [];
        foreach ($this->getData() as $webhook) {
            $webhooks[] = new Webhook($webhook);
        }

        return $webhooks;
    }
}
