<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpcomingInstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dueAmount = round((float) $this->total_due_amount - (float) $this->paid_amount, 2);
        $order = $this->loan?->orderItem?->order;
        $totalInstallments = (int) ($this->loan?->installments_count ?? 0);
        $currentInstallmentNo = (int) ($this->installment_no ?? 0);

        return [
            'id' => $this->id,
            'order' => $order ? new OrderResource($order) : null,
            'installments_label' => ($currentInstallmentNo > 0 && $totalInstallments > 0)
                ? "{$currentInstallmentNo} of {$totalInstallments} installments"
                : null,
            'due_amount' => formatCurrency(max($dueAmount, 0)),
            'due_at' => $this->due_at?->format('d M'),
            'product_name' => $this->loan?->orderItem?->product_name ?? $this->loan?->orderItem?->listing?->product_name ?? 'Outdated',
        ];
    }
}
