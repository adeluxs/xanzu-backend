<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingAttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->label,
            'price' => (string) $this->price,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'has_discount' => $this->discount_amount > 0,
            'final_price' => $this->final_price,
            'qty' => $this->qty,
        ];
    }
}
