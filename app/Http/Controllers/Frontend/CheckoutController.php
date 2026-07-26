<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ListingType;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\DepositMethod;
use App\Models\Listing;
use App\Models\ListingAttribute;
use App\Models\SubscriptionPlan;
use App\Traits\NotifyTrait;
use App\Traits\Payment;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use NotifyTrait, Payment;

    /**
     * Buy Now — single product (legacy).
     * Stores a single-item checkout session.
     */
    public function buyNow(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:listings,id'],
            'quantity' => ['required', 'integer'],
        ]);

        $listing = Listing::public()->find($request->product_id);

        if (!$listing) {
            notify()->error(__('Product may not available!'));

            return back();
        }

        if ($listing->is_out_of_stock) {
            notify()->error(__('Product is out of stock!'));

            return back();
        } elseif ($listing->status == 0) {
            notify()->error(__('Product is not available!'));

            return back();
        } elseif ($listing->seller_id == auth()->id()) {
            notify()->error(__('You can not purchase your own product!'));

            return back();
        } elseif ($request->quantity <= 0) {
            notify()->error(__('Invalid quantity!'));

            return back();
        } elseif ($listing->quantity < $request->quantity) {
            notify()->error(__('Requested quantity not available!'));

            return back();
        }

        $unitPrice = (float) $listing->final_price;
        if (!empty($request->selected_attributes) && $listing->has_attributes) {
            $attrs = ListingAttribute::whereIn('id', (array) $request->selected_attributes)
                ->where('listing_id', $listing->id)
                ->get();
            $unitPrice += (float) $attrs->sum('final_price');
        }

        $lineTotal = $unitPrice * $request->quantity;

        $checkoutData = [
            // Multi-item structure (single item wrapped in array)
            'items' => [
                [
                    'product_id' => $listing->id,
                    'quantity' => $request->quantity,
                    'line_total' => $lineTotal,
                    'selected_attributes' => $request->selected_attributes ?? null,
                ],
            ],
            'subtotal' => $lineTotal,
            'finalPrice' => $lineTotal,
            // kept for backward-compat in views
            'product_id' => $listing->id,
            'quantity' => $request->quantity,
        ];

        if ($request->coupon) {
            $coupon = Coupon::approved()->whereCode($request->coupon)->first();

            $error = match (true) {
                !$coupon => __('Invalid coupon!'),
                $coupon->expires_at->isPast() => __('Coupon expired!'),
                $coupon->max_use_limit <= $coupon->total_used => __('Coupon limit reached!'),
                $coupon->status == 0 => __('Coupon disabled!'),
                $coupon->seller_id != $listing->seller_id => __('Coupon not applicable!'),
                default => null
            };

            if ($error !== null) {
                notify()->error($error);

                return back();
            }
            $checkoutData['coupon_id'] = $coupon->id;
            $checkoutData['coupon_discount_amount'] = $coupon->discount_type == 'percentage' ? ($coupon->discount_value / 100) * $lineTotal : $coupon->discount_value;
            $checkoutData['finalPrice'] = $checkoutData['finalPrice'] - $checkoutData['coupon_discount_amount'];
        }

        session([
            'checkout' => $checkoutData,
        ]);

        return to_route('checkout');
    }

    /**
     * Add to Cart / Multi-Item Checkout.
     * Accepts an array of items and builds the session.
     */
    public function addToCheckout(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:listings,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $checkoutItems = [];
        $subtotal = 0;
        $hasPhysical = false;
        $hasDigital = false;

        foreach ($request->items as $raw) {
            $listing = Listing::public()->find($raw['product_id']);

            if (!$listing) {
                notify()->error(__('Product :name may not be available!', ['name' => $raw['product_id']]));

                return back();
            }

            if ($listing->is_out_of_stock || $listing->quantity < $raw['quantity']) {
                notify()->error(__(':name is out of stock!', ['name' => $listing->product_name]));

                return back();
            }

            if ($listing->seller_id == auth()->id()) {
                notify()->error(__('You can not purchase your own product!'));

                return back();
            }

            if ($listing->type === ListingType::PHYSICAL) {
                $hasPhysical = true;
            }

            if ($listing->type === ListingType::DIGITAL) {
                $hasDigital = true;
            }

            if ($hasPhysical && $hasDigital) {
                notify()->error(__('Physical and digital products cannot be purchased in the same order.'));

                return back();
            }

            $unitPrice = (float) $listing->final_price;
            if (!empty($raw['selected_attributes']) && $listing->has_attributes) {
                $attrs = ListingAttribute::whereIn('id', (array) $raw['selected_attributes'])
                    ->where('listing_id', $listing->id)
                    ->get();
                $unitPrice += (float) $attrs->sum('final_price');
            }

            $lineTotal = $unitPrice * $raw['quantity'];
            $subtotal += $lineTotal;

            $checkoutItems[] = [
                'product_id' => $listing->id,
                'quantity' => $raw['quantity'],
                'line_total' => $lineTotal,
                'selected_attributes' => $raw['selected_attributes'] ?? null,
            ];
        }

        $checkoutData = [
            'items' => $checkoutItems,
            'subtotal' => $subtotal,
            'finalPrice' => $subtotal,
            'shipping_charge_amount' => $request->shipping_charge_amount ?? 0,
            'shipping_charge_type' => $request->shipping_charge_type ?? 'fixed',
            'shipping_address' => $request->shipping_address ?? null,
        ];

        // Apply coupon if provided
        if ($request->coupon) {
            $coupon = Coupon::approved()->whereCode($request->coupon)->first();
            if ($coupon && !$coupon->expires_at->isPast() && $coupon->max_use_limit > $coupon->total_used) {
                $discount = $coupon->discount_type == 'percentage'
                    ? ($coupon->discount_value / 100) * $subtotal
                    : $coupon->discount_value;
                $checkoutData['coupon_id'] = $coupon->id;
                $checkoutData['coupon_discount_amount'] = $discount;
                $checkoutData['finalPrice'] -= $discount;
            }
        }

        // Add shipping to final price
        $shippingCharge = ($checkoutData['shipping_charge_type'] === 'percentage')
            ? round($subtotal * ($checkoutData['shipping_charge_amount'] ?? 0) / 100, 2)
            : ($checkoutData['shipping_charge_amount'] ?? 0);

        $checkoutData['finalPrice'] += $shippingCharge;

        session(['checkout' => $checkoutData]);

        return to_route('checkout');
    }

    public function checkout(Request $request, $type = null, $data = null)
    {

        if ($type == 'plan') {
            $plan = SubscriptionPlan::findOrFail($request->data);
            $planPrice = $plan->price;
            $checkout['total'] = $checkout['subtotal'] = $checkout['finalPrice'] = $planPrice;
            $checkout['plan_data'] = $plan;

            if (!auth()->user()->is_seller) {
                notify()->error(__('You must be a seller to purchase a subscription plan.'));

                return back();
            }

            session([
                'checkout' => $checkout,
            ]);

            return view('frontend::checkout.index', compact('checkout'));
        }
        $checkout = session('checkout');

        // check if checkout session is empty
        if (!$checkout) {
            notify()->error(__('Checkout session expired!'));

            return back();
        }

        // Validate items availability
        $items = $checkout['items'] ?? [];
        $listings = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $listing = Listing::findOrFail($item['product_id']);

                if ($listing->is_out_of_stock) {
                    notify()->error(__(':name is out of stock!', ['name' => $listing->product_name]));

                    return back();
                } elseif ($listing->quantity < $item['quantity']) {
                    notify()->error(__('Requested quantity not available for :name!', ['name' => $listing->product_name]));

                    return back();
                } elseif ($listing->seller_id == auth()->id()) {
                    notify()->error(__('You can not purchase your own product!'));

                    return redirect($listing->url);
                }

                $listings[] = $listing;
            }

            if ($this->hasMixedListingTypes(collect($listings))) {
                notify()->error(__('Physical and digital products cannot be purchased in the same order.'));

                return back();
            }
        } else {
            // Legacy single-product fallback
            $listing = Listing::findOrFail($checkout['product_id'] ?? 0);

            if ($listing->is_out_of_stock) {
                notify()->error(__('Product is out of stock!'));

                return back();
            } elseif (($checkout['quantity'] ?? 0) <= 0) {
                notify()->error(__('Invalid quantity!'));

                return back();
            } elseif ($listing->quantity < ($checkout['quantity'] ?? 0)) {
                notify()->error(__('Requested quantity not available!'));

                return back();
            } elseif ($listing->seller_id == auth()->id()) {
                notify()->error(__('You can not purchase your own product!'));

                return redirect($listing->url);
            }

            $listings[] = $listing;
        }

        $checkout['total'] = $checkout['finalPrice'];
        $checkout['coupon'] = Coupon::find($checkout['coupon_id'] ?? null);

        // Pass first listing for backward-compat in the view
        $listing = $listings[0] ?? null;

        return view('frontend::checkout.index', compact('listing', 'listings', 'checkout'));
    }

    public function payment(Request $request)
    {
        $request->validate([
            'paymentMethod' => in_array($request->paymentMethod, ['topup', 'balance']) ? 'nullable' : 'required',
        ]);

        if (session('checkout') == null) {
            notify()->error(__('Checkout session expired!'));

            return to_buyerSellerRoute('dashboard');
        }

        if ($request->paymentMethod == 'topup' && auth()->user()->balance < session('checkout')['finalPrice']) {
            notify()->error(__('Insufficient balance!'));

            return back();
        }

        $error = match (true) {
            in_array($request->paymentMethod, ['balance']) && auth()->user()->balance < session('checkout')['finalPrice'] => __('Insufficient Balance.'),
            in_array($request->paymentMethod, ['topup']) && auth()->user()->balance < session('checkout')['finalPrice'] => __('Insufficient Balance.'),
            default => null
        };

        if ($error !== null) {
            notify()->error($error);

            return back();
        }

        // check if plan

        if (isset(session('checkout')['plan_data'])) {

            $subscription = app(SubscriptionController::class);
            $request->merge([
                'plan_id' => session('checkout')['plan_data']->id,
                'method' => in_array($request->paymentMethod, ['topup', 'balance']) ? $request->paymentMethod : 'gateway',
                'gateway_code' => $request->paymentMethod ?? null,
            ]);

            return $subscription->subscriptionNow($request);
        }

        $gateway = DepositMethod::where('gateway_code', $request->paymentMethod)->first();
        if (!in_array($request->paymentMethod, ['topup', 'balance']) && !$gateway) {
            notify()->error(__('Invalid payment method!'));

            return back();
        }

        $gateway_code = ucwords($gateway?->gateway_code) ?? null;

        $service = orderService();

        // Build data array from session for the refactored OrderService
        $checkout = session('checkout');
        $orderData = [
            'items' => collect($checkout['items'] ?? [])->map(fn($i) => [
                'listing_id' => $i['product_id'],
                'quantity' => $i['quantity'],
                'selected_attributes' => $i['selected_attributes'] ?? null,
                'plan_id' => $i['plan_id'] ?? null,
            ])->toArray(),
            'coupon_code' => isset($checkout['coupon_id']) ? Coupon::find($checkout['coupon_id'])?->code : null,
            'shipping_address' => $checkout['shipping_address'] ?? null,
            'shipping_charge_amount' => $checkout['shipping_charge_amount'] ?? 0,
            'shipping_charge_type' => $checkout['shipping_charge_type'] ?? 'fixed',
            'gateway_code' => $gateway_code,
            'is_topup' => false,
        ];

        // Fallback for legacy single-item checkout (no 'items' key)
        if (empty($orderData['items']) && !empty($checkout['product_id'])) {
            $orderData['items'] = [
                [
                    'listing_id' => $checkout['product_id'],
                    'quantity' => $checkout['quantity'] ?? 1,
                    'selected_attributes' => $checkout['selected_attributes'] ?? null,
                    'plan_id' => $checkout['plan_id'] ?? null,
                ],
            ];
        }

        $listingIds = collect($orderData['items'])->pluck('listing_id')->filter()->unique()->values();
        $types = Listing::query()->whereIn('id', $listingIds)->pluck('type')->unique();
        if ($types->contains(ListingType::PHYSICAL->value) && $types->contains(ListingType::DIGITAL->value)) {
            notify()->error(__('Physical and digital products cannot be purchased in the same order.'));

            return back();
        }

        try {
            $order = $service->create($orderData, $request);
        } catch (\Exception $e) {
            $listingId = $checkout['product_id'] ?? ($checkout['items'][0]['product_id'] ?? 0);
            notify()->error($e->getMessage());

            return to_route('listing.details', Listing::findOrFail($listingId)?->slug);
        }

        if (!$order) {
            $listingId = $checkout['product_id'] ?? ($checkout['items'][0]['product_id'] ?? 0);

            when(!session()->has('notify'), fn() => notify()->error(__('Can not create order!')));

            return to_route('listing.details', Listing::findOrFail($listingId)?->slug);
        }
        $service->dismissSession();
        $order = $order->refresh();

        if ($request->paymentMethod == 'topup') {
            $order->buyer()->decrement('balance', $order->total_price);
            $service->orderPaymentSuccess($order, true);
            notify()->success(__('Purchase successful!'));

            return to_buyerSellerRoute('purchase.success', $order->order_number);
        } elseif ($request->paymentMethod == 'balance') {
            $order->buyer()->decrement('balance', $order->total_price);
            $service->orderPaymentSuccess($order, true);
            notify()->success(__('Purchase successful!'));

            return to_buyerSellerRoute('purchase.success', $order->order_number);
        }

        $order->transaction->order = $order;
        $order->transaction->listing = $order->listing;

        return $this->depositAutoGateway($gateway->gateway_code, $order->transaction);
    }

    private function hasMixedListingTypes($listings): bool
    {
        $types = collect($listings)->pluck('type')->map(fn($type) => $type?->value ?? $type)->filter()->unique();

        return $types->contains(ListingType::PHYSICAL->value) && $types->contains(ListingType::DIGITAL->value);
    }
}
