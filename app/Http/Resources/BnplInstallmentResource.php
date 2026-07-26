<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BnplInstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dueAmount = round((float) $this->total_due_amount - (float) $this->paid_amount, 2);

        return [
            'id' => $this->id,
            'installment_no' => $this->installment_no,
            'principal_amount' => (float) $this->principal_amount,
            'interest_amount' => (float) $this->interest_amount,
            'late_fee_amount' => (float) $this->late_fee_amount,
            'total_due_amount' => (float) $this->total_due_amount,
            'paid_amount' => (float) $this->paid_amount,
            'due_amount' => max($dueAmount, 0),
            'status' => $this->status,
            'due_at' => $this->due_at?->format('d M Y'),
            'paid_at' => $this->paid_at?->format('d M Y'),
        ];
    }
}
