<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class WebhookDeliveryList extends AbstractListResult
{
    /**
     * @return WebhookDelivery[]
     */
    public function all(): array
    {
        $webhookDeliveries = [];
        foreach ($this->getData() as $webhookDelivery) {
            $webhookDeliveries[] = new WebhookDelivery($webhookDelivery);
        }

        return $webhookDeliveries;
    }
}
