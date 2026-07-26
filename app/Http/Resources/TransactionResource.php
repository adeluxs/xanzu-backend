<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $orderedItems = $this->order?->getProductNames();
        return [
            'id' => $this->id,
            'tnx' => $this->tnx,
            'description' => $this->description,
            'amount' => formatCurrency($this->amount, $this->currency),
            'charge' => formatCurrency($this->charge, $this->currency),
            'final_amount' => formatCurrency($this->final_amount, $this->currency),
            'order_items_name' => $orderedItems && !empty($orderedItems) ? ('Purchased: ' . $orderedItems) : null,
            'type' => $this->type,
            'status' => $this->status,
            'method' => strtoupper($this->method),
            'pay_currency' => $this->pay_currency ?? setting('site_currency'),
            'pay_amount' => $this->pay_amount !== null ? formatCurrency($this->pay_amount, $this->pay_currency) : null,
            'order_id' => $this->order_id,
            'created_at' => $this->created_at,
            'day' => $this->day,
        ];
    }
}
