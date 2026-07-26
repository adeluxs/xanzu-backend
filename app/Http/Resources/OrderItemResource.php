<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($request->has('full') && $this->listing?->type?->value == 'digital') {
            $deliveredItems = $this->order->deliveryItem()->where('listing_id', $this->listing_id)->get();
        } else {
            $deliveredItems = null;
        }

        return [
            'id' => $this->id,
            'listing_id' => $this->listing_id,
            'product_name' => $this->product_name ?? $this->listing?->product_name ?? data_get($this->order->bnplCheckoutSession?->items, '0.name') ?? null,
            'product_image' => $this->product_image ?? $this->listing?->thumbnail ? asset($this->listing->thumbnail) : data_get($this->order->bnplCheckoutSession?->items, '0.image') ?? null,
            'product_type' => $this->listing?->type,
            'seller_id' => $this->seller_id,
            'seller_name' => $this->seller?->full_name,
            'category' => $this->listing?->category?->name,
            'is_topup' => (bool) $this->is_topup,
            'quantity' => $this->quantity,
            'org_unit_price' => $this->org_unit_price,
            'unit_price' => number_format($this->total_price / $this->quantity, 2),
            'total_price' => $this->total_price,
            'selected_attributes' => $this->when($request->has('full'), function () {
                return collect($this->selected_attributes)->map(function ($attr) {
                    $attr['price'] = number_format($attr['price'], 2);
                    return $attr;
                })->toArray();
            }),
            'status' => $this->status,
            'delivered_items_count' => $this->when(($request->has('full') && ($deliveredItems)), $deliveredItems?->count() ?? 0),
            'delivered_items' => $this->when(($request->has('full') && ($deliveredItems)), $deliveredItems),
        ];
    }
}
