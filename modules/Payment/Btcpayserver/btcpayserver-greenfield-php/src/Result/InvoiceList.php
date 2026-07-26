<?php

declare(strict_types=1);

namespace BTCPayServer\Result;

class InvoiceList extends AbstractListResult
{
    /**
     * @return Invoice[]
     */
    public function all(): array
    {
        $invoices = [];
        foreach ($this->getData() as $invoice) {
            $invoices[] = new Invoice($invoice);
        }

        return $invoices;
    }

    /**
     * @return Invoice[]
     */
    public function getInvoicesByStatus(string $status): array
    {
        $r = array_filter(
            $this->all(),
            function (Invoice $invoice) use ($status) {
                return $invoice->getStatus() === $status;
            }
        );

        // Renumber results
        return array_values($r);
    }
}
