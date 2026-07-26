<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BnplItemLoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'credit_limit_split_id' => $this->credit_limit_split_id,
            'total_item_amount' => (float) $this->total_item_amount,
            'initial_paid_amount' => (float) $this->initial_paid_amount,
            'final_amount_to_pay' => (float) $this->final_amount_to_pay,
            'remaining_due_amount' => (float) $this->remaining_due_amount,
            'total_split' => (int) $this->total_split,
            'payment_interval_amount' => (int) $this->payment_interval_amount,
            'payment_interval_type' => $this->payment_interval_type,
            'interest_rate_amount' => (float) $this->interest_rate_amount,
            'interest_rate_type' => $this->interest_rate_type,
            'delay_fine_amount' => (float) $this->delay_fine_amount,
            'delay_fine_type' => $this->delay_fine_type,
            'status' => $this->status,
            'installments' => BnplInstallmentResource::collection($this->whenLoaded('installments')),
        ];
    }
}
