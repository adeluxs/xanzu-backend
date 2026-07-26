<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Enums\OrderStatus;
use App\Enums\TxnStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use ApiResponse;

    public function pendingCount(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $pendingStatuses = [
            OrderStatus::Pending->value,
            OrderStatus::WaitingForDelivery->value,
        ];

        $query = Order::query()
            ->whereHas('items', function ($query) use ($user) {
                $query->where('seller_id', $user->id);
            })
            ->whereIn('payment_status', [TxnStatus::Success->value, TxnStatus::PartiallyPaid->value])
            ->whereIn('status', $pendingStatuses);

        $count = $query->count();

        return $this->successResponse(
            data: ['count' => $count]
        );
    }


    public function items(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in(array_column(OrderStatus::cases(), 'value'))],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $items = OrderItem::query()
            ->where('seller_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', [TxnStatus::Success->value, TxnStatus::PartiallyPaid->value]);
            })
            ->with([
                'order:id,order_number,status,order_date,payment_status,delivered_at,buyer_id',
                'order.buyer:id,first_name,last_name,email,phone',
                'listing:id,product_name,thumbnail,type,delivery_method',
            ])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (string) $request->input('status'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($orderQuery) use ($search) {
                            $orderQuery->where('order_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('listing', function ($listingQuery) use ($search) {
                            $listingQuery->where('product_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate((int) $request->input('per_page', 20));

        $serialized = collect($items->items())->map(fn(OrderItem $item) => $this->serializeItem($item))->values();

        return $this->successResponse(
            data: ['items' => $serialized],
            meta: [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ]
        );
    }

    public function itemDetails(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $item = OrderItem::query()
            ->where('seller_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', [TxnStatus::Success->value, TxnStatus::PartiallyPaid->value]);
            })
            ->with([
                'order:id,order_number,status,order_date,payment_status,shipping_address,delivered_at,buyer_id',
                'order.buyer:id,first_name,last_name,email,phone',
                'listing:id,product_name,thumbnail,type,delivery_method',
            ])
            ->find($id);

        if (!$item) {
            return $this->notFoundResponse(__('Order item not found.'));
        }


        return $this->successResponse(
            data: ['item' => $this->serializeItem($item, true)]
        );
    }

    public function updateItemStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([OrderStatus::Delivered->value])],
        ]);

        $item = OrderItem::query()
            ->where('seller_id', $user->id)
            ->with('order:id,status,delivered_at,payment_status')
            ->find($id);

        if (!$item) {
            return $this->notFoundResponse(__('Order item not found.'));
        }

        $allowedCurrentStatuses = [
            OrderStatus::Pending->value,
            OrderStatus::WaitingForDelivery->value,
        ];

        if (!in_array((string) $item->status, $allowedCurrentStatuses, true)) {
            return $this->validationErrorResponse(__('Only pending or waiting for delivery order items can be marked as delivered.'));
        }

        $order = $item->order;
        $canBeDeliveredByPayment = in_array((string) $order?->payment_status, [TxnStatus::Success->value, TxnStatus::PartiallyPaid->value], true);

        if (!$canBeDeliveredByPayment) {
            return $this->validationErrorResponse(__('This order is not eligible for delivery yet. Order Payment Status: :orderPaymentStatus', ['orderPaymentStatus' => $order?->payment_status]));
        }

        if (!$order || !in_array((string) $order->status, $allowedCurrentStatuses, true)) {
            return $this->validationErrorResponse(__('Only pending or waiting for delivery orders can be moved to delivered.'));
        }

        $item->update(['status' => $validated['status']]);
        orderService()->notifyDeliveredOrderItem($item);

        $hasPendingLikeItems = OrderItem::query()
            ->where('order_id', $item->order_id)
            ->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Success->value,
                OrderStatus::WaitingForDelivery->value,
            ])
            ->exists();

        if (!$hasPendingLikeItems) {
            $order->update([
                'status' => OrderStatus::Delivered->value,
                'delivered_at' => $order->delivered_at ?? now(),
            ]);
        }

        $item->load([
            'order:id,order_number,status,order_date,payment_status,delivered_at,buyer_id',
            'order.buyer:id,first_name,last_name,email,phone',
            'listing:id,product_name,thumbnail,type,delivery_method',
        ]);

        return $this->successResponse(
            data: ['item' => $this->serializeItem($item)],
            message: __('Order item status updated successfully.')
        );
    }

    public function deliveryItems(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $item = OrderItem::query()
            ->where('seller_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', [TxnStatus::Success->value, TxnStatus::PartiallyPaid->value]);
            })
            ->with(['listing:id,product_name,type,delivery_method'])
            ->find($id);

        if (!$item) {
            return $this->notFoundResponse(__('Order item not found.'));
        }

        if (!$item->listing || (string) $item->listing->type->value !== 'digital') {
            return $this->validationErrorResponse(__('Delivery items can only be managed for digital listings.'));
        }

        $listing = $item->listing;

        $assigned = DeliveryItem::query()
            ->where('listing_id', $listing->id)
            ->where('order_id', $item->order_id)
            ->orderBy('id')
            ->get(['id', 'listing_id', 'order_id', 'data', 'is_used']);

        $unused = DeliveryItem::query()
            ->where('listing_id', $listing->id)
            ->whereNull('order_id')
            ->orderBy('id')
            ->get(['id', 'listing_id', 'order_id', 'data', 'is_used']);

        return $this->successResponse(data: [
            'order_item' => [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'order_status' => $item->order?->status,
                'item_status' => $item->status,
                'quantity' => (int) $item->quantity,
                'listing_id' => $listing->id,
                'listing_name' => $listing->product_name,
                'delivery_method' => $listing->delivery_method,
            ],
            'assigned_delivery_items' => $assigned,
            'unused_delivery_items' => $unused,
        ]);
    }

    public function storeDeliveryItems(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }


        $validation = Validator::make($request->all(), [
            'delivery_items' => ['required', 'array', 'min:1'],
            'delivery_items.*' => ['required', 'string'],
        ]);

        if ($validation->fails()) {
            return $this->validationErrorResponse($validation->errors());
        }


        $validated = $validation->valid();


        $item = OrderItem::query()
            ->where('seller_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', [TxnStatus::Success->value, TxnStatus::PartiallyPaid->value]);
            })
            ->with(['order.items:id,order_id,status', 'listing:id,product_name,type,delivery_method'])
            ->find($id);

        if (!$item) {
            return $this->notFoundResponse(__('Order item not found.'));
        }


        if (!$item->listing || (string) $item->listing->type->value !== 'digital') {
            return $this->validationErrorResponse(__('Delivery items can only be managed for digital listings.'));
        }

        $listing = $item->listing;
        $qty = max(0, (int) $item->quantity);

        if ($qty === 0) {
            return $this->validationErrorResponse(__('This order item has zero quantity.'));
        }

        $incoming = array_values($validated['delivery_items']);
        if (count($incoming) > $qty) {
            return $this->validationErrorResponse(__('Delivery items cannot be more than order item quantity (:qty).', ['qty' => $qty]));
        }

        DB::beginTransaction();

        $assigned = DeliveryItem::query()
            ->where('listing_id', $listing->id)
            ->where('order_id', $item->order_id)
            ->orderBy('id')
            ->get();

        $missing = $qty - $assigned->count();
        for ($i = 0; $i < $missing; $i++) {
            DeliveryItem::query()->create([
                'listing_id' => $listing->id,
                'order_id' => $item->order_id,
                'data' => null,
                'is_used' => 0,
            ]);
        }

        $assigned = DeliveryItem::query()
            ->where('listing_id', $listing->id)
            ->where('order_id', $item->order_id)
            ->orderBy('id')
            ->take($qty)
            ->get();

        foreach ($incoming as $index => $value) {
            $record = $assigned->get($index);
            if (!$record) {
                break;
            }

            $record->update([
                'data' => (string) $value,
                'is_used' => 1,
            ]);
        }

        $item->update(['status' => OrderStatus::Delivered->value]);
        orderService()->notifyDeliveredOrderItem($item);

        DB::commit();
        $item->refresh();
        $item->load([
            'order:id,order_number,status,order_date,payment_status,delivered_at,buyer_id',
            'order.buyer:id,first_name,last_name,email,phone',
            'listing:id,product_name,thumbnail,type,delivery_method',
        ]);

        return $this->successResponse(
            data: [
                'item' => $this->serializeItem($item),
            ],
            message: __('Delivery items updated successfully.')
        );
    }

    public function updateDeliveryItem(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $validated = $request->validate([
            'delivery_items' => ['required', 'array', 'min:1'],
            'delivery_items.*.id' => ['required', 'integer', 'distinct'],
            'delivery_items.*.data' => ['required', 'string'],
        ]);

        $item = OrderItem::query()
            ->where('seller_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', [TxnStatus::Success->value, TxnStatus::PartiallyPaid->value]);
            })
            ->with(['listing:id,type'])
            ->find($id);

        if (!$item) {
            return $this->notFoundResponse(__('Order item not found.'));
        }

        if (!$item->listing || (string) $item->listing->type->value !== 'digital') {
            return $this->validationErrorResponse(__('Delivery items can only be managed for digital listings.'));
        }

        $payloadItems = collect($validated['delivery_items']);
        $deliveryItemIds = $payloadItems->pluck('id')->map(fn($idValue) => (int) $idValue)->values();

        $deliveryItems = DeliveryItem::query()
            ->whereIn('id', $deliveryItemIds->all())
            ->where('listing_id', $item->listing_id)
            ->get()
            ->keyBy('id');

        if ($deliveryItems->count() !== $deliveryItemIds->count()) {
            return $this->notFoundResponse(__('One or more delivery items were not found.'));
        }

        foreach ($payloadItems as $payloadItem) {
            $deliveryItem = $deliveryItems->get((int) $payloadItem['id']);
            if (!$deliveryItem) {
                return $this->notFoundResponse(__('One or more delivery items were not found.'));
            }

            if ($deliveryItem->order_id && (int) $deliveryItem->order_id !== (int) $item->order_id) {
                return $this->validationErrorResponse(__('Selected delivery item belongs to another order.'));
            }
        }

        foreach ($payloadItems as $payloadItem) {
            $deliveryItem = $deliveryItems->get((int) $payloadItem['id']);
            $deliveryItem->update([
                'data' => (string) $payloadItem['data'],
                'order_id' => $deliveryItem->order_id ?: $item->order_id,
                'is_used' => 1,
            ]);
        }

        orderService()->deliverReadyWaitingOrdersForListing($item->listing, (int) $item->order_id);

        // make the item as delivered if all delivery items are assigned and marked as used
        $readyForDelivery = DeliveryItem::query()
            ->where('listing_id', $item->listing_id)
            ->where('order_id', $item->order_id)
            ->where('is_used', 1)
            ->count();
        if ($readyForDelivery > 0 && $readyForDelivery === DeliveryItem::query()->where('listing_id', $item->listing_id)->where('order_id', $item->order_id)->count()) {
            $item->update(['status' => OrderStatus::Delivered->value]);
            orderService()->notifyDeliveredOrderItem($item);
        }

        $updatedDeliveryItems = DeliveryItem::query()
            ->whereIn('id', $deliveryItemIds->all())
            ->orderBy('id')
            ->get();

        return $this->successResponse(
            data: ['delivery_items' => $updatedDeliveryItems],
            message: __('Delivery items updated successfully.')
        );
    }

    private function serializeItem(OrderItem $item, bool $withShipping = false): array
    {
        $listing = $item->listing;
        $order = $item->order;
        $buyer = $order?->buyer;

        $data = [
            'id' => $item->id,
            'order_id' => $item->order_id,
            'order_number' => $order?->order_number,
            'order_status' => $order?->status,
            'order_date' => $order?->order_date,
            'item_status' => $item->status,
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'total_price' => (float) $item->total_price,
            'product_name' => $listing?->product_name ?? $item->product_name,
            'product_image' => $listing?->thumbnail ? asset($listing->thumbnail) : $item->product_image,
            'listing_id' => $item->listing_id,
            'listing_type' => $listing?->type,
            'delivery_method' => $listing?->delivery_method,
            'buyer' => [
                'id' => $buyer?->id,
                'name' => $buyer?->full_name,
                'email' => $buyer?->email,
                'phone' => $buyer?->phone,
            ],
            'shipping_address' => $order?->shipping_address,
            'can_mark_delivered' => in_array((string) $item->status, [
                OrderStatus::Pending->value,
                OrderStatus::WaitingForDelivery->value,
            ], true),
            'delivery_items' => $item->order->deliveryItem()->where('listing_id', $item->listing_id)->get(['id', 'data']),
        ];

        if ($withShipping) {
            $data['shipping_address'] = $order?->shipping_address;
        }

        return $data;
    }
}
