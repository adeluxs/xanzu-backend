<?php

namespace App\Services;

use App\Enums\BnplLoanStatus;
use App\Enums\ListingType;
use App\Enums\OrderStatus;
use App\Enums\ReferralType;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\Coupon;
use App\Models\CreditLimitSplit;
use App\Models\DeliveryItem;
use App\Models\Gateway;
use App\Models\LevelReferral;
use App\Models\Listing;
use App\Models\ListingAttribute;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CouponService;
use App\Services\OrderNumberService;
use App\Services\ShippingCalculator;
use App\Traits\BnplOrderServiceTrait;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    use BnplOrderServiceTrait, ImageUpload, NotifyTrait;

    private const EXTERNAL_LISTING_ID = 0;

    /* ================================================================
     |  create order from arr
     |
     |  $orderData:
     |    items[]          – each: listing_id, quantity, selected_attributes (attr ids)
     |    coupon_code      – optional coupon code string
     |    shipping_address – optional string/json
     |    gateway_code     – payment gateway code
     |    is_topup         – bool (default false)
     |    topup_amount     – required when is_topup = true
     |    manual_field_data – optional manual deposit fields
     | ================================================================ */

    public function create(array $orderData, ?Request $request = null)
    {
        $isBnpl = (bool) ($orderData['is_bnpl'] ?? false);
        $gatewayCode = $orderData['gateway_code'] ?? null;

        DB::beginTransaction();
        try {
            $orderNumber = app(OrderNumberService::class)->generateNextNumber();
            $checkoutItems = $this->resolveItems($orderData);
            $this->validateListingTypes($checkoutItems);
            $subtotal = collect($checkoutItems)->sum('line_total');

            // coupon
            $couponResult = ['coupon_id' => null, 'discount_amount' => 0];
            if (! empty($orderData['coupon_code'])) {
                $couponResult = app(CouponService::class)->validateAndCalculate(
                    $orderData['coupon_code'],
                    $subtotal
                );
            }
            throw_if(
                $couponResult['discount_amount'] >= $subtotal,
                fn () => ValidationException::withMessages(['coupon_code' => __('Discount amount cannot be greater than or equal to subtotal.')])
            );

            // shipping
            $shippingResult = app(ShippingCalculator::class)->calculate(
                $checkoutItems,
                [
                    'charge_amount' => setting('shipping_charge', 'fee') ?? 0,
                    'charge_type' => setting('shipping_charge_type', 'fee') ?? 'fixed',
                ]
            );

            // final price
            $finalPrice = max(0, $subtotal - $couponResult['discount_amount'] + $shippingResult['final_shipping_charge']);

            if ($isBnpl) {
                $payAmount = $finalAmount = $finalPrice;
                $charge = 0;
                $gatewayInfo = null;
            } else {
                [$payAmount, $charge, $finalAmount, $gatewayInfo] = gatewayPayAmount($gatewayCode, $finalPrice);
            }

            // create order
            $order = Order::create([
                'buyer_id' => auth()->id(),
                'order_number' => $orderNumber,
                'coupon_id' => $couponResult['coupon_id'],
                'subtotal' => $subtotal,
                'discount_amount' => $couponResult['discount_amount'],
                'shipping_charge_amount' => $shippingResult['shipping_charge_amount'],
                'shipping_charge_type' => $shippingResult['shipping_charge_type'],
                'final_shipping_charge' => $shippingResult['final_shipping_charge'],
                'total_price' => $finalPrice,
                'status' => OrderStatus::Pending->value,
                'payment_status' => TxnStatus::Pending->value,
                'gateway_id' => $isBnpl ? null : Gateway::where('gateway_code', $gatewayCode)?->first()?->id,
                'shipping_address' => $this->parseShippingAddress($orderData['shipping_address'] ?? null),
                'is_bnpl' => $isBnpl,
                'bnpl_upfront_amount' => 0,
            ]);

            $this->createOrderItems($order, $checkoutItems);

            $authUser = auth()->user();

            // buyer transaction
            $buyerTransaction = Transaction::create([
                'user_id' => $authUser->id,
                'order_id' => $order->id,
                'tnx' => 'TRX'.strtoupper(Str::random(10)),
                'description' => ($isBnpl ? 'BNPL Order Placed #'.$order->order_number : 'Order Placed #'.$order->order_number),
                'amount' => $finalPrice,
                'charge' => $charge,
                'type' => TxnType::ProductOrder->value,
                'status' => TxnStatus::Pending->value,
                'pay_currency' => $gatewayInfo?->currency ?? setting('site_currency', 'global'),
                'pay_amount' => $payAmount,
                'final_amount' => $finalAmount,
                'manual_field_data' => json_encode($orderData['customFields'] ?? processManualDepositData($request, 'customFields') ?? []),
                'method' => $isBnpl ? 'bnpl' : ($gatewayCode ?? 'system'),
            ]);

            $this->createSellerTransactions($buyerTransaction, $order);

            DB::commit();

            $this->orderCreatedNotify($order);

            return $order;
        } catch (Exception $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function createOutsideBnplOrderFromCard(User $buyer, array $data): Order
    {
        DB::beginTransaction();
        try {
            $orderNumber = app(OrderNumberService::class)->generateNextNumber();

            $amount = round((float) ($data['amount'] ?? 0), 2);

            $order = Order::create([
                'buyer_id' => $buyer->id,
                'order_number' => $orderNumber,
                'coupon_id' => null,
                'subtotal' => $amount,
                'discount_amount' => 0,
                'shipping_charge_amount' => 0,
                'shipping_charge_type' => 'fixed',
                'final_shipping_charge' => 0,
                'total_price' => $amount,
                'status' => OrderStatus::Pending->value,
                'payment_status' => TxnStatus::Pending->value,
                'gateway_id' => null,
                'shipping_address' => null,
                'is_bnpl' => true,
                'bnpl_upfront_amount' => 0,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'listing_id' => self::EXTERNAL_LISTING_ID,
                'seller_id' => null,
                'category_id' => null,
                'plan_id' => null,
                'is_topup' => 0,
                'product_name' => $data['product_name'] ?? 'Card Purchase',
                'quantity' => 1,
                'org_unit_price' => $amount,
                'unit_price' => $amount,
                'total_price' => $amount,
                'selected_attributes' => null,
                'status' => OrderStatus::Pending->value,
            ]);

            Transaction::create([
                'user_id' => $buyer->id,
                'order_id' => $order->id,
                'tnx' => $data['txn_id'] ?? null,
                'description' => $data['description'] ?? ('Card Purchase #'.$order->order_number),
                'amount' => $amount,
                'charge' => 0,
                'type' => TxnType::ProductOrder->value,
                'status' => TxnStatus::Pending->value,
                'pay_currency' => $data['currency'] ?? setting('site_currency', 'global'),
                'pay_amount' => $amount,
                'final_amount' => $amount,
                'manual_field_data' => json_encode($data['manual_field_data'] ?? []),
                'method' => $data['method'] ?? 'credit_card',
            ]);

            DB::commit();

            return $order->refresh()->load(['items', 'transaction']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createExternalBnplOrder(User $buyer, User $merchant, array $data): Order
    {
        DB::beginTransaction();

        try {
            $orderNumber = app(OrderNumberService::class)->generateNextNumber();

            $amount = round((float) ($data['amount'] ?? 0), 2);
            if ($amount <= 0) {
                throw new Exception('Invalid BNPL amount.');
            }

            $currency = (string) ($data['currency'] ?? setting('site_currency', 'global'));
            $items = array_values((array) ($data['items'] ?? []));
            $customer = (array) ($data['customer'] ?? []);
            $providerPlatform = (string) ($data['provider_platform'] ?? 'wordpress-woocommerce');
            $providerSource = (string) ($data['provider_source'] ?? 'woocommerce_plugin');

            $totalQuantity = max(1, (int) collect($items)->sum(function ($item) {
                return max(1, (int) data_get($item, 'quantity', 1));
            }));

            $productName = $this->summarizeExternalItems($items, (string) ($data['merchant_order_id'] ?? ''));
            $manualFieldData = [
                'source' => 'woocommerce',
                'provider_source' => $providerSource,
                'provider_platform' => $providerPlatform,
                'merchant_id' => $merchant->id,
                'merchant_order_id' => (string) ($data['merchant_order_id'] ?? ''),
                'merchant_reference_id' => (string) ($data['merchant_reference_id'] ?? ''),
                'merchant_public_key' => (string) ($merchant->public_key ?? ''),
                'session_id' => (string) ($data['session_id'] ?? ''),
                'customer' => $customer,
                'items' => $items,
            ];

            $order = Order::create([
                'buyer_id' => $buyer->id,
                'order_number' => $orderNumber,
                'coupon_id' => null,
                'subtotal' => $amount,
                'discount_amount' => 0,
                'shipping_charge_amount' => 0,
                'shipping_charge_type' => 'fixed',
                'final_shipping_charge' => 0,
                'total_price' => $amount,
                'status' => OrderStatus::Pending->value,
                'payment_status' => TxnStatus::Pending->value,
                'gateway_id' => null,
                'shipping_address' => null,
                'is_bnpl' => true,
                'bnpl_upfront_amount' => 0,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'listing_id' => self::EXTERNAL_LISTING_ID,
                'seller_id' => $merchant->id,
                'category_id' => null,
                'plan_id' => null,
                'is_topup' => 0,
                'product_name' => $productName,
                'product_image' => data_get($items, '0.image') ?? null,
                'quantity' => $totalQuantity,
                'org_unit_price' => round($amount / $totalQuantity, 2),
                'unit_price' => round($amount / $totalQuantity, 2),
                'total_price' => $amount,
                'selected_attributes' => null,
                'status' => OrderStatus::Pending->value,
            ]);

            Transaction::create([
                'user_id' => $buyer->id,
                'order_id' => $order->id,
                'description' => 'WooCommerce BNPL Order #'.$order->order_number,
                'amount' => $amount,
                'charge' => 0,
                'type' => TxnType::ProductOrder->value,
                'status' => TxnStatus::Pending->value,
                'pay_currency' => $currency,
                'pay_amount' => $amount,
                'final_amount' => $amount,
                'manual_field_data' => json_encode($manualFieldData),
                'method' => 'bnpl_woocommerce',
            ]);

            Transaction::create([
                'user_id' => $merchant->id,
                'order_id' => $order->id,
                'description' => 'WooCommerce Product Sold #'.$order->order_number,
                'amount' => $amount,
                'charge' => 0,
                'type' => TxnType::ProductSold->value,
                'status' => TxnStatus::Pending->value,
                'pay_currency' => $currency,
                'pay_amount' => $amount,
                'final_amount' => $amount,
                'manual_field_data' => json_encode($manualFieldData + ['credited_to_balance' => false]),
                'method' => 'bnpl_woocommerce',
            ]);

            DB::commit();

            return $order->refresh()->load(['items', 'transaction', 'transactions']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function creditSellerOrderProceeds(Order $order): void
    {
        DB::beginTransaction();
        try {
            $sellerMetrics = $order->items()
                ->selectRaw('seller_id, SUM(quantity) as total_quantity, SUM(total_price) as total_amount')
                ->whereNotNull('seller_id')
                ->groupBy('seller_id')
                ->get()
                ->keyBy('seller_id');

            $sellerTransactions = Transaction::query()
                ->where('order_id', $order->id)
                ->where('type', TxnType::ProductSold->value)
                ->lockForUpdate()
                ->get();

            foreach ($sellerTransactions as $sellerTransaction) {
                $manualFieldData = json_decode((string) $sellerTransaction->manual_field_data, true) ?: [];
                if ((bool) data_get($manualFieldData, 'credited_to_balance')) {
                    continue;
                }

                $seller = User::query()->whereKey($sellerTransaction->user_id)->lockForUpdate()->first();
                if (! $seller) {
                    continue;
                }

                $seller->increment('balance', (float) $sellerTransaction->amount);
                $seller->increment('total_sold', (int) data_get($sellerMetrics, $sellerTransaction->user_id.'.total_quantity', 0));
                $seller->increment('total_amount_sold', (float) data_get($sellerMetrics, $sellerTransaction->user_id.'.total_amount', $sellerTransaction->amount));

                $manualFieldData['credited_to_balance'] = true;
                $manualFieldData['credited_at'] = now()->toIso8601String();

                $sellerTransaction->update([
                    'status' => TxnStatus::Success->value,
                    'manual_field_data' => json_encode($manualFieldData),
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function resolveDefaultSplitIdForUser(User $user): ?int
    {
        $defaultSplitId = (int) ($user->default_split ?? 0);
        if ($defaultSplitId > 0) {
            $split = CreditLimitSplit::query()->active()->whereKey($defaultSplitId)->first();
            if ($split) {
                return $split->id;
            }
        }

        return CreditLimitSplit::query()->active()->orderBy('id')->value('id');
    }

    private function summarizeExternalItems(array $items, string $merchantOrderId = ''): string
    {
        $items = collect($items)->filter(fn ($item) => is_array($item))->values();
        $firstName = trim((string) data_get($items->first(), 'name', ''));

        if ($items->count() <= 1) {
            return $firstName !== '' ? $firstName : ('WooCommerce Order #'.($merchantOrderId !== '' ? $merchantOrderId : 'External'));
        }

        if ($firstName === '') {
            return 'WooCommerce Order #'.($merchantOrderId !== '' ? $merchantOrderId : 'External');
        }

        return $firstName.' +'.($items->count() - 1).' more';
    }

    /* ================================================================
     |  resolve items from data array
     |
     |  each item in $orderData['items'] should have:
     |    listing_id, quantity, selected_attributes (array of attr ids)
     | ================================================================ */

    private function resolveItems(array $orderData): array
    {

        $resolved = [];

        foreach ($orderData['items'] as $item) {
            $listing = Listing::findOrFail($item['listing_id']);
            $qty = (int) ($item['quantity'] ?? 1);

            // Base listing final price is always included; selected attributes are additive.
            $baseUnitPrice = $listing->final_price;

            $unitPrice = $baseUnitPrice;
            $selectedAttr = [];

            if (! empty($item['selected_attributes']) && $listing->has_attributes) {
                $selectedAttrIds = collect((array) $item['selected_attributes'])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                $attrs = ListingAttribute::whereIn('id', $selectedAttrIds)
                    ->where('listing_id', $listing->id)
                    ->get()
                    ->keyBy('id');

                if ($attrs->count() !== count($selectedAttrIds)) {
                    throw new Exception('One or more selected attribute(s) are invalid for "'.($listing->product_name ?? 'product').'"');
                }

                if ($attrs->isNotEmpty()) {
                    $hasStockForAll = $attrs->every(fn ($a) => ($a->qty ?? 0) >= $qty);

                    if (! $hasStockForAll) {
                        throw new Exception('Selected attribute(s) are out of stock for "'.($listing->product_name ?? 'product').'"');
                    }

                    $attributePrice = (float) $attrs->sum('final_price');
                    $unitPrice = $baseUnitPrice + $attributePrice;

                    $selectedAttr = $attrs->map(fn ($a) => [
                        'id' => $a->id,
                        'group' => $a->group,
                        'label' => $a->label,
                        'price' => (float) $a->final_price,
                        'qty' => $a->qty ?? 0,
                        'discount_type' => $a->discount_type,
                        'discount_amount' => $a->discount_amount,
                        'price_before_discount' => $a->price,
                    ])->toArray();
                }
            }

            $resolved[] = [
                'listing' => $listing,
                'quantity' => $qty,
                'line_total' => round($unitPrice * $qty, 2),
                'selected_attributes' => $selectedAttr,
                'plan_id' => $item['plan_id'] ?? null,
            ];
        }

        return $resolved;
    }

    private function validateListingTypes(array $checkoutItems): void
    {
        $listingTypes = collect($checkoutItems)
            ->map(fn (array $item) => $item['listing']?->type?->value ?? $item['listing']?->type)
            ->filter()
            ->unique();

        throw_if(
            $listingTypes->contains(ListingType::PHYSICAL->value) && $listingTypes->contains(ListingType::DIGITAL->value),
            fn () => ValidationException::withMessages([
                'items' => __('Physical and digital products cannot be purchased in the same order.'),
            ])
        );

        $digitalItemsCount = collect($checkoutItems)
            ->filter(fn (array $item) => (($item['listing']?->type?->value ?? $item['listing']?->type) === ListingType::DIGITAL->value))
            ->count();

        throw_if(
            $digitalItemsCount > 1,
            fn () => ValidationException::withMessages([
                'items' => __('You can only purchase one digital product per order.'),
            ])
        );
    }

    private function createOrderItems(Order $order, array $checkoutItems): void
    {
        foreach ($checkoutItems as $item) {
            $listing = $item['listing'];

            OrderItem::create([
                'order_id' => $order->id,
                'listing_id' => $listing->id ?? 0,
                'seller_id' => $listing->seller_id ?? null,
                'category_id' => $listing->category_id ?? null,
                'plan_id' => $item['plan_id'] ?? null,
                'is_topup' => false,
                'quantity' => $item['quantity'],
                'org_unit_price' => ($listing->price ?? 0),
                'unit_price' => ($listing->final_price ?? 0),
                'total_price' => $item['line_total'],
                'selected_attributes' => $item['selected_attributes'] ?? null,
                'status' => OrderStatus::Pending->value,
            ]);
        }
    }

    private function createSellerTransactions(Transaction $buyerTransaction, Order $order): void
    {
        if ($buyerTransaction->type !== TxnType::ProductOrder) {
            return;
        }

        $itemsBySeller = $order->items->groupBy('seller_id');

        foreach ($itemsBySeller as $sellerId => $sellerItems) {
            if (! $sellerId) {
                continue;
            }

            $sellerTotal = $sellerItems->sum('total_price');

            $sellerTransaction = $buyerTransaction->replicate();
            $sellerTransaction->user_id = $sellerId;
            $sellerTransaction->type = TxnType::ProductSold->value;
            $sellerTransaction->description = 'Product Sold #'.$order->order_number;
            $sellerTransaction->tnx = 'TRX'.strtoupper(Str::random(10));
            $sellerTransaction->final_amount = $sellerTransaction->amount = $sellerTotal;
            $sellerTransaction->charge = 0;
            $sellerTransaction->save();
        }
    }

    private function parseShippingAddress(mixed $address): ?array
    {
        if (! $address) {
            return null;
        }

        return is_array($address) ? $address : json_decode($address, true);
    }

    public function dismissSession()
    {
        session()->forget(['checkout', 'order_id', 'topup']);
    }

    public function orderCreatedNotify(Order $order)
    {
        $buyer = $order->buyer ?? auth()->user();

        $shortcodes = [
            '[[full_name]]' => $buyer->full_name,
            '[[email]]' => $buyer->email,
            '[[order_number]]' => $order->order_number,
            '[[order_date]]' => $order->order_date,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[product_names]]' => $order->getProductNames(),
            '[[quantity]]' => $order->quantity,
            '[[total_price]]' => $order->total_price.' '.setting('site_currency', 'global'),
            '[[payment_status]]' => ucwords($order->payment_status),
            '[[order_status]]' => ucwords($order->status),
            '[[invoice_link]]' => frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false),
        ];

        return $this->sendNotify(
            $buyer->email,
            'order_placed',
            'User',
            $shortcodes,
            $buyer->phone,
            $buyer->id,
            frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false)
        );
    }

    public function orderPaymentCompletedNotify(Order $order)
    {
        $buyer = $order->buyer;

        $shortcodes = [
            '[[full_name]]' => $buyer->full_name,
            '[[email]]' => $buyer->email,
            '[[order_number]]' => $order->order_number,
            '[[payment_date]]' => now()->format('Y-m-d'),
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[payment_amount]]' => $order->total_price.' '.setting('site_currency', 'global'),
            '[[payment_status]]' => ucwords($order->payment_status),
            '[[order_status]]' => ucwords($order->status),
            '[[invoice_link]]' => frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false),
        ];

        $this->sendNotify(
            $buyer->email,
            'order_payment_completed',
            'User',
            $shortcodes,
            $buyer->phone,
            $buyer->id,
            frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false)
        );

        return true;
    }

    public function orderDeliveryWithNotify(Order $order)
    {
        if (! $this->isApiProductOrder($order)) {
            return false;
        }

        if (in_array($order->status, [OrderStatus::Delivered->value, OrderStatus::Completed->value], true)) {
            return true;
        }

        $allDelivered = true;

        foreach ($order->items as $orderItem) {
            if ((string) $orderItem->status === OrderStatus::Delivered->value) {
                continue;
            }

            $listing = $orderItem->listing;

            if (! $listing) {
                $allDelivered = false;

                continue;
            }

            $isDigital = $listing->type == ListingType::DIGITAL;

            if ($isDigital) {
                $deliveryItem = $listing->deliveryItems()
                    ->where(function ($q) use ($order) {
                        $q->whereNull('order_id')->orWhere('order_id', $order->id);
                    })
                    ->whereNotNull('data')->oldest('id')->take($orderItem->quantity);

                if ($deliveryItem->count() < $orderItem->quantity) {

                    $orderItem->update(['status' => OrderStatus::WaitingForDelivery->value]);
                    $order->update(['status' => OrderStatus::WaitingForDelivery->value]);

                    $deliveryItemEmpty = $listing->deliveryItems()->whereNull('order_id')->whereNull('data')->oldest('id');
                    $deliveryItemEmptyCount = $deliveryItemEmpty->count();
                    $deliveryItemEmpty->take($orderItem->quantity)->update(['order_id' => $order->id]);

                    if ($orderItem->quantity > $deliveryItemEmptyCount) {
                        DeliveryItem::createNew($orderItem->quantity - $deliveryItemEmptyCount, $listing, $order->id);
                    }

                    $this->orderWaitingForDeliveryNotify($order, $orderItem);
                    $allDelivered = false;

                    continue;
                }

                $deliveryItem->update(['order_id' => $order->id]);
                $deliveryItemsList[] = $deliveryItem->pluck('data')->implode('<br>');
                $deliveryItem->update(['is_used' => 1]);

                $orderItem->update(['status' => OrderStatus::Delivered->value]);

                $this->sendItemWiseDeliveryNotify($orderItem);

                continue;
            }

            if ($listing->type == ListingType::PHYSICAL) {
                $allDelivered = false;

                if ((string) $orderItem->status !== OrderStatus::WaitingForDelivery->value) {
                    $orderItem->update(['status' => OrderStatus::WaitingForDelivery->value]);
                    $this->orderWaitingForDeliveryNotify($order, $orderItem);
                }
            }
        }

        if ($allDelivered) {
            $order->update([
                'status' => OrderStatus::Delivered->value,
                'delivered_at' => now(),
            ]);
            $this->allItemsDeliveryNotify($order);
        }

        return $allDelivered;
    }

    public function sendFullOrderDeliveryNotify(Order $order)
    {
        $buyer = $order->buyer;
        $order->load('items.listing');

        $shortcodes = [
            '[[full_name]]' => $buyer->full_name,
            '[[email]]' => $buyer->email,
            '[[order_number]]' => $order->order_number,
            '[[delivery_date]]' => now()->format('Y-m-d'),
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[delivery_items]]' => $order->getProductNames(),
            '[[invoice_link]]' => frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false),
        ];

        $this->sendNotify(
            $buyer->email,
            'order_delivery',
            'User',
            $shortcodes,
            $buyer->phone,
            $buyer->id,
            frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false)
        );
    }

    public function sendItemWiseDeliveryNotify(OrderItem $orderItem)
    {
        $buyer = $orderItem->order->buyer;
        $order = $orderItem->order;

        $deliveryItemsList = $orderItem->listing->deliveryItems()
            ->where('order_id', $order->id)
            ->whereNotNull('data')
            ->pluck('data')
            ->toArray();

        $shortcodes = [
            '[[full_name]]' => $buyer->full_name,
            '[[email]]' => $buyer->email,
            '[[order_number]]' => $order->order_number,
            '[[delivery_date]]' => now()->format('Y-m-d'),
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[delivery_items]]' => ($orderItem->product_name ?? $orderItem->listing->product_name).': '.implode('<br>', $deliveryItemsList),
            '[[invoice_link]]' => frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false),
        ];

        $this->sendNotify(
            $buyer->email,
            'order_delivery',
            'User',
            $shortcodes,
            $buyer->phone,
            $buyer->id,
            frontendPanelUrl('purchase.index', null, false)
        );
    }

    public function deliverReadyWaitingOrdersForListing(Listing $listing, ?int $priorityOrderId = null): int
    {
        $deliverableItemStatuses = [
            OrderStatus::Pending->value,
            OrderStatus::WaitingForDelivery->value,
        ];

        $waitingOrderIds = Order::query()
            ->where('status', OrderStatus::WaitingForDelivery->value)
            ->whereHas('items', function ($query) use ($listing, $deliverableItemStatuses) {
                $query->where('listing_id', $listing->id)
                    ->whereIn('status', $deliverableItemStatuses);
            })
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($priorityOrderId) {
            $waitingOrderIds = collect([$priorityOrderId])
                ->merge($waitingOrderIds)
                ->unique()
                ->values()
                ->all();
        }

        $deliveredCount = 0;

        foreach ($waitingOrderIds as $orderId) {
            $requiredQuantity = OrderItem::query()
                ->where('order_id', $orderId)
                ->where('listing_id', $listing->id)
                ->whereIn('status', $deliverableItemStatuses)
                ->sum('quantity');

            if ($requiredQuantity <= 0) {
                continue;
            }

            $availableQuantity = $listing->deliveryItems()
                ->where(function ($query) use ($orderId) {
                    $query->whereNull('order_id')
                        ->orWhere('order_id', $orderId);
                })
                ->whereNotNull('data')
                ->where('is_used', 0)
                ->count();

            if ($availableQuantity < $requiredQuantity) {
                continue;
            }

            $order = Order::query()->with(['items.listing', 'buyer'])->find($orderId);

            if (! $order) {
                continue;
            }

            if ($this->orderDeliveryWithNotify($order)) {
                $deliveredCount++;
            }
        }

        return $deliveredCount;
    }

    public function allItemsDeliveryNotify(Order $order): bool
    {
        if ($order->items()->where('status', '!=', OrderStatus::Delivered->value)->count() === 0) {
            $this->sendFullOrderDeliveryNotify($order);

            return true;
        }

        return false;
    }

    public function notifyDeliveredOrderItem(OrderItem $orderItem): void
    {
        $orderItem->loadMissing(['order.buyer', 'listing']);
        $order = $orderItem->order;
        if (! $order || ! $order->buyer) {
            return;
        }

        if ($orderItem->listing && $orderItem->listing->type == ListingType::DIGITAL) {
            $this->sendItemWiseDeliveryNotify($orderItem);
        } else {
            $buyer = $order->buyer;
            $shortcodes = [
                '[[full_name]]' => $buyer->full_name,
                '[[email]]' => $buyer->email,
                '[[order_number]]' => $order->order_number,
                '[[delivery_date]]' => now()->format('Y-m-d'),
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_url]]' => route('home'),
                '[[delivery_items]]' => $orderItem->listing?->product_name ?? $orderItem->product_name ?? __('Order item'),
                '[[invoice_link]]' => frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false),
            ];

            $this->sendNotify(
                $buyer->email,
                'order_delivery',
                'User',
                $shortcodes,
                $buyer->phone,
                $buyer->id,
                frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false)
            );
        }

        if ($this->allItemsDeliveryNotify($order)) {
            $order->update([
                'status' => OrderStatus::Delivered->value,
                'delivered_at' => $order->delivered_at ?? now(),
            ]);
        }
    }

    public function orderWaitingForDeliveryNotify(Order $order, ?OrderItem $orderItem = null)
    {
        $listing = $orderItem?->listing ?? $order->items->first()?->listing;
        $fallbackProductName = $orderItem?->product_name ?? $order->items->first()?->product_name;

        if (! $listing && ! $fallbackProductName) {
            return;
        }

        $buyer = $order->buyer;
        $deliveryItemsRoute = route('admin.listing.delivery-items', [
            'id' => $listing->id,
            'order_id' => $order->id,
        ]);

        $adminShortcodes = [
            '[[seller_name]]' => setting('site_title', 'global').' Admin',
            '[[order_number]]' => $order->order_number,
            '[[full_name]]' => $buyer->full_name,
            '[[email]]' => $buyer->email,
            '[[update_items_link]]' => $deliveryItemsRoute,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
        ];

        if (! $orderItem) {
            $orderItems = $order->items()->where('status', OrderStatus::WaitingForDelivery->value)->with('seller')->get();
            if ($orderItems->isEmpty()) {
                $orderItems = $order->items()->with('seller')->get();
            }
        } else {
            $orderItems = [$orderItem->load('seller')];
        }

        foreach ($orderItems as $item) {
            $hasSeller = $item->seller_id && $item->seller;
            $notifyEmail = $hasSeller ? $item->seller->email : setting('site_email', 'global');
            $notifyPhone = $hasSeller ? $item->seller->phone : null;
            $this->sendNotify(
                $notifyEmail,
                $hasSeller ? 'waiting_for_delivery_merchant' : 'waiting_for_delivery_seller',
                $hasSeller ? 'User' : 'Admin',
                $adminShortcodes,
                $notifyPhone,
                $hasSeller ? $item->seller_id : null,
                $deliveryItemsRoute
            );
        }

        $buyerShortcodes = [
            '[[full_name]]' => $buyer->full_name,
            '[[email]]' => $buyer->email,
            '[[order_number]]' => $order->order_number,
            '[[delivery_date]]' => now()->format('Y-m-d'),
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[delivery_items]]' => $listing?->product_name ?? $fallbackProductName,
            '[[invoice_link]]' => frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false),
        ];

        $this->sendNotify(
            $buyer->email,
            'waiting_for_delivery_buyer',
            'User',
            $buyerShortcodes,
            $buyer->phone,
            $buyer->id,
            frontendPanelUrl('purchase.invoice', $order->order_number ?? 0, false)
        );
    }

    public function setTrnxId(Order $order, $trnxId)
    {
        $order?->update(['transaction_id' => $trnxId]);
    }

    public function getSellerTrnx(Order $order)
    {
        return Transaction::where('order_id', $order->id)->where('type', TxnType::ProductSold)->get();
    }

    public function orderPaymentSuccess(Order $order, $sessionDismiss = true)
    {
        DB::beginTransaction();
        $wasPaymentSuccess = $order->payment_status === TxnStatus::Success->value;
        $isApiOrder = $this->isApiProductOrder($order);

        $this->orderPaymentCompletedNotify($order);

        if (! $order->is_topup) {
            foreach ($order->items as $orderItem) {
                if ($orderItem->listing && $orderItem->quantity > $orderItem->listing->quantity) {
                    $this->setOrderRefunded($order, $sessionDismiss, true);
                    notify()->error(__('Quantity is not available for :product!', ['product' => $orderItem->listing->product_name]));

                    return false;
                }
            }
        }

        $allDigital = $order->items->every(fn ($i) => $i->listing?->type == ListingType::DIGITAL);
        $hasPhysical = $order->items->contains(fn ($i) => $i->listing?->type == ListingType::PHYSICAL);
        $paymentStatus = TxnStatus::Success->value;
        if ($order->is_bnpl) {
            $loan = $order->bnplItemLoans()->with('installments')->first();
            if ($loan && $loan->status !== BnplLoanStatus::Paid->value) {
                $hasPaidInstallment = $loan->installments->contains(fn ($installment) => $installment->status === BnplLoanStatus::Paid->value);
                $paymentStatus = ((float) $loan->initial_paid_amount > 0 || $hasPaidInstallment)
                    ? TxnStatus::PartiallyPaid->value
                    : TxnStatus::Pending->value;
            }
        }

        if (! in_array($order->status, [OrderStatus::WaitingForDelivery->value, OrderStatus::Delivered->value, OrderStatus::Completed->value], true)) {
            $order->update([
                'payment_status' => $paymentStatus,
                'status' => $isApiOrder
                    ? ($allDigital
                        ? OrderStatus::WaitingForDelivery->value
                        : ($hasPhysical ? OrderStatus::WaitingForDelivery->value : OrderStatus::Pending->value))
                    : $order->status,
            ]);

        } else {
            $order->update([
                'payment_status' => $paymentStatus,
            ]);
        }

        if ($isApiOrder && $order->status == OrderStatus::WaitingForDelivery->value) {
            $this->orderDeliveryWithNotify($order);
        }

        $order->transactions()->update(['status' => TxnStatus::Success->value]);

        if ($order->coupon_id && ! $wasPaymentSuccess) {
            $order->coupon()->increment('total_used');
        }

        if ($sessionDismiss && $allDigital) {
            $order->refresh();
            $this->setOrderDelivered($order);
            $this->dismissSession();
        }

        DB::commit();

        return $order;
    }

    private function isApiProductOrder(Order $order): bool
    {
        $transaction = $order->transaction;
        if (! $transaction) {
            return true;
        }

        if (str_contains((string) $transaction->method, 'woocommerce')) {
            return false;
        }

        $manualFieldData = json_decode((string) $transaction->manual_field_data, true) ?: [];
        $source = strtolower((string) data_get($manualFieldData, 'source', ''));
        $providerPlatform = strtolower((string) data_get($manualFieldData, 'provider_platform', ''));

        if ($source === 'woocommerce' || str_contains($providerPlatform, 'woocommerce')) {
            return false;
        }

        return true;
    }

    public function setOrderDelivered(Order $order)
    {
        if ($order->transaction->type == TxnType::ProductOrder) {
            $this->creditSellerOrderProceeds($order);
        }

        foreach ($order->items as $orderItem) {
            if ($orderItem->listing) {
                $orderItem->listing->increment('sold_count', $orderItem->quantity);
                $this->decrementOrderItemStock($orderItem);
            }
        }

        if (! $order->is_topup) {
            $order->buyer()->increment('total_purchased');
            $order->buyer()->increment('total_amount_purchased', $order->total_price);
        }

        if (setting('deposit_level') && $order->transaction->type == TxnType::Topup) {
            $level = LevelReferral::where('type', 'topup')->max('the_order');

            creditReferralBonus($order->transaction->user, ReferralType::Topup->value, $order->transaction->amount - $order->transaction->charge, $level);
        }

        if (setting('product_order_level') && $order->transaction->type == TxnType::ProductOrder) {
            $level = LevelReferral::where('type', 'product_order')->max('the_order');
            creditReferralBonus($order->transaction->user, ReferralType::ProductOrder->value, $order->transaction->amount - $order->transaction->charge, $level);
        }

        if ($order->is_topup) {
            $topupItem = $order->items->first();
            $topupAmount = $topupItem ? $topupItem->unit_price : $order->total_price;
            $order->buyer()->increment($order->buyer->is_seller ? 'balance' : 'balance', $topupAmount);
        } else {
            $this->orderDeliveryWithNotify($order);
        }
    }

    private function decrementOrderItemStock(OrderItem $orderItem): void
    {
        $listing = $orderItem->listing;
        if (! $listing) {
            return;
        }

        $selectedAttributes = collect($orderItem->selected_attributes ?? [])
            ->map(fn ($attr) => is_array($attr) ? $attr : [])
            ->filter(fn ($attr) => ! empty($attr['id']))
            ->values();

        if ($selectedAttributes->isEmpty()) {
            $listing->decrement('quantity', $orderItem->quantity);

            return;
        }

        $attributeIds = $selectedAttributes->pluck('id')->map(fn ($id) => (int) $id)->all();

        $attributes = ListingAttribute::query()
            ->where('listing_id', $listing->id)
            ->whereIn('id', $attributeIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($selectedAttributes as $selectedAttribute) {
            $attributeId = (int) $selectedAttribute['id'];
            $attribute = $attributes->get($attributeId);
            if (! $attribute) {
                continue;
            }

            $currentQty = max(0, (int) $attribute->qty);
            if ($currentQty <= 0) {
                continue;
            }

            $decrementBy = min($currentQty, (int) $orderItem->quantity);
            $attribute->decrement('qty', $decrementBy);
        }

        $listing->decrement('quantity', $orderItem->quantity);
    }

    public function setOrderFailed(Order $order, $sessionDismiss = true)
    {
        DB::beginTransaction();
        $order->update(['payment_status' => TxnStatus::Failed->value, 'status' => OrderStatus::Failed->value]);
        $order->items()->update(['status' => OrderStatus::Failed->value]);
        $order->transaction()->update(['status' => TxnStatus::Failed->value]);
        $this->releaseBnplCredit($order, 'failed_release');
        DB::commit();

        if ($sessionDismiss) {
            $this->dismissSession();
        }
    }

    public function setOrderRefunded(Order $order, $sessionDismiss = true, $qtyFailed = false)
    {
        DB::beginTransaction();
        $order->update(['payment_status' => TxnStatus::Failed->value, 'status' => OrderStatus::Refunded->value]);
        $order->items()->update(['status' => OrderStatus::Refunded->value]);
        $order->transaction()->update(['status' => TxnStatus::Failed->value]);

        $refundAmount = (float) $order->total_price;
        if ($order->is_bnpl ?? false) {
            $bnplLoan = $order->bnplItemLoans()->first();
            $paidInstallmentAmount = $bnplLoan
                ? (float) $bnplLoan->installments()->sum('paid_amount')
                : 0;

            $refundAmount = round($paidInstallmentAmount, 2);

            if ($refundAmount <= 0) {
                $refundAmount = round((float) ($order->bnpl_upfront_amount ?? 0), 2);
            }
        }

        if ($refundAmount > 0) {
            $order->buyer()->increment('balance', $refundAmount);
            (new Txn)->new($refundAmount, 0, $refundAmount, 'system', 'Refund for Order #'.$order->order_number, TxnType::OrderRefunded, TxnStatus::Success, null, null, $order->buyer_id);
        }
        $this->releaseBnplCredit($order, 'refund_release');

        if (! $qtyFailed) {
            foreach ($order->items as $orderItem) {
                if ($orderItem->seller_id) {
                    $seller = User::find($orderItem->seller_id);
                    if ($seller) {
                        $seller->decrement('total_sold', $orderItem->quantity);
                        $seller->decrement('total_amount_sold', $orderItem->total_price);
                        $seller->decrement('balance', $orderItem->total_price);
                    }
                }
                if ($orderItem->listing) {
                    $orderItem->listing->decrement('sold_count', $orderItem->quantity);
                    $orderItem->listing->increment('quantity', $orderItem->quantity);
                }
            }
        }

        if ($order->coupon_id) {
            $order->coupon()->decrement('total_used');
        }

        if ($order->transaction->type == TxnType::ProductOrder) {
            $sellerTrxQuery = Transaction::where('order_id', $order->id)->where('type', TxnType::ProductSold);
            $sellerTrxQuery->update(['status' => TxnStatus::Refunded->value]);
        }

        foreach ($order->items as $orderItem) {
            if ($orderItem->listing) {
                $orderItem->listing->deliveryItems()->where('order_id', $order->id)->update(['order_id' => null, 'is_used' => 0]);
            }
        }

        DB::commit();

        if ($sessionDismiss) {
            $this->dismissSession();
        }
    }

    public function setOrderCancelled(Order $order, $sessionDismiss = true)
    {
        DB::beginTransaction();
        $order->update(['payment_status' => TxnStatus::Cancelled->value, 'status' => OrderStatus::Cancelled->value]);
        $order->items()->update(['status' => OrderStatus::Cancelled->value]);
        $order->transaction()->update(['status' => TxnStatus::Cancelled->value]);

        // if bnpl order, release credit immediately
        $this->releaseBnplCredit($order, 'cancel_release');
        DB::commit();

        if ($sessionDismiss) {
            $this->dismissSession();
        }
    }
}
