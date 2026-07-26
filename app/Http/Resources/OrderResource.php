<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'subtotal' => $this->when($request->has('full'), fn() => $this->subtotal),
            'discount_amount' => $this->when($request->has('full'), fn() => $this->discount_amount),
            'shipping_charge_amount' => $this->when($request->has('full'), fn() => $this->shipping_charge_amount),
            'shipping_charge_type' => $this->shipping_charge_type,
            'final_shipping_charge' => $this->when($request->has('full'), fn() => $this->final_shipping_charge),
            'total_price' => $this->when($request->has('full'), fn() => $this->total_price),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'is_bnpl' => (bool) ($this->is_bnpl ?? false),
            'is_active_bnpl' => $this->isActiveBnpl(),
            'total_installments' => $this->is_bnpl ? ($this->totalInstallments() ?? 0) : null,
            'paid_installments' => $this->is_bnpl ? ($this->paidInstallments() ?? 0) : null,
            'bnpl_upfront_amount' => (float) ($this->bnpl_upfront_amount ?? 0),
            'bnpl_details' => $this->when(
                (bool) ($this->is_bnpl ?? false) && !$request->has('full'),
                fn() => BnplItemLoanResource::make($this->whenLoaded('bnplItemLoans'))
            ),
            'delivery_info' => $this->when($request->has('full'), fn() => [
                'courier' => $this->courierPartner ? [
                    'id' => $this->courierPartner->id,
                    'name' => $this->courierPartner->name,
                    'logo' => asset($this->courierPartner->logo),
                    'description' => $this->courierPartner->short_description,
                ] : null,
                'estimated_delivery_from' => $this->estimated_delivery_from?->format('Y-m-d'),
                'estimated_delivery_to' => $this->estimated_delivery_to?->format('Y-m-d'),
                'estimated_delivery' => $this->estimatedDelivery() ?? null,
                'tracking_number' => $this->tracking_number,
                'tracking_link' => $this->tracking_link,
                'warehouse_address' => setting('address', 'global'),
                'show_delivery_date' => in_array($this->status, ['waiting_for_delivery', 'delivered', 'completed']) && $this->estimatedDelivery(),
            ]),
            'shipping_address' => $this->shipping_address,
            'order_date' => $this->order_date,
            'currency' => setting('site_currency', 'global'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'products_name' => $this->getProductNames(),
            'transaction' => $this->when($request->has('full'), fn() => $this->whenLoaded('transaction', fn() => [
                'tnx' => $this->transaction->tnx,
                'amount' => $this->transaction->amount,
                'charge' => $this->transaction->charge,
                'pay_amount' => $this->transaction->pay_amount,
                'pay_currency' => $this->transaction->pay_currency,
                'method' => $this->transaction->method,
                'status' => $this->transaction->status,
            ])),
            'delivery_item' => $this->when($request->has('full'), fn() => $this->whenLoaded('deliveryItem', function () {
                $deliveryItems = $this->deliveryItem;
                if (!$deliveryItems || $deliveryItems->isEmpty()) {
                    return null;
                }
                $formatted = [];
                foreach ($deliveryItems as $item) {
                    $formatted[] = [
                        'listing_id' => $item->listing_id,
                        'data' => $item->data
                    ];
                }
                return $formatted;
            })),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function frontend($orders)
    {
        $soldItems = [];
        foreach ($orders as $key => $order) {
            $orderItem = $order->items->map(function ($item) use ($order) {
                return [
                    'order_id' => $order->bnplCheckoutSession?->merchant_order_id ?? $order->id . $item->id,
                    'customer_name' => $order->is_bnpl ? $item->order?->buyer?->full_name ?? data_get($order->bnplCheckoutSession?->customer, 'name') ?? null : null,
                    'customer_image' => $order->is_bnpl ? $item->order?->buyer?->avatarPath ?? data_get($order->bnplCheckoutSession?->customer, 'image') ?? User::getDefaultAvatar() : User::getDefaultAvatar(),
                    'product_name' => $item->listing?->product_name ?? $item->product_name,
                    'product_image' => $item->listing?->product_image ? asset($item->listing->product_image) : data_get($order->bnplCheckoutSession?->items, '0.image') ?? null,
                    'quantity' => $item->quantity,
                    'type' => $item->type,
                    'total_price' => $item->total_price,
                    'category' => $item->listing?->category?->name ?? null,

                ];
            })->first();
            unset($orderItem['id']);
            $soldItems[] = $orderItem;
        }
        return $soldItems;
    }
}
