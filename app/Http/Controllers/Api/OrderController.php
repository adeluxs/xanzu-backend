<?php

namespace App\Http\Controllers\Api;

use App\Enums\ListingReview as ListingReviewEnum;
use App\Enums\ListingType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Backend\ReviewController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PayBnplInstallmentRequest;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Http\Requests\Api\TopupRequest;
use App\Http\Resources\BnplInstallmentResource;
use App\Http\Resources\BnplItemLoanResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UpcomingInstallmentResource;
use App\Models\BnplInstallment;
use App\Models\DepositMethod;
use App\Models\ListingReview;
use App\Models\Order;
use App\Models\ShippingAddress;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use App\Traits\ImageUpload;
use App\Traits\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use ApiResponse, ImageUpload, Payment;

    /* ================================================================
     |  LIST ORDERS (buyer's orders)
     | ================================================================ */

    public function index(Request $request)
    {
        $orders = Order::where('buyer_id', $request->user()->id)
            ->with(['items.bnplLoan.installments', 'transaction', 'bnplItemLoans.installments'])
            ->latest()
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('order_number'), function ($query) use ($request) {
                $query->where('order_number', $request->order_number);
            })
            ->paginate($request->per_page ?? 15);

        return $this->successResponse(
            data: OrderResource::collection($orders),
            meta: [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        );
    }

    /* ================================================================
     |  SHOW SINGLE ORDER
     | ================================================================ */

    public function show(Request $request, $orderNumber)
    {
        $order = Order::where('buyer_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->with(['items.listing', 'deliveryItem', 'items.seller', 'items.bnplLoan.installments', 'transaction', 'bnplItemLoans.installments', 'courierPartner'])
            ->first();
        if (!$order) {
            return $this->notFoundResponse(__('Order not found.'));
        }


        $request->merge(['full' => true]); // to include full details in the resource
        return $this->successResponse(new OrderResource($order));
    }

    /**
     * Post a review for a listing in an order
     */
    public function postReview(Request $request, $orderNumber)
    {
        $request->validate([
            'rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'max:500'],
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['image', 'max:2000'],
        ]);

        $order = Order::where('buyer_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->with('items.listing')
            ->first();

        if (!$order) {
            return $this->notFoundResponse(__('Order not found.'));
        }

        // Only allow reviews for orders that are delivered or completed
        if (!in_array($order->status, [OrderStatus::Delivered->value, OrderStatus::Completed->value])) {
            return $this->validationErrorResponse(__('Reviews can only be submitted after the order is delivered or completed.'));
        }

        $orderItemId = (int) $request->input('order_item_id');

        // Ensure the order_item_id belongs to this order
        $orderItem = $order->items->firstWhere('id', $orderItemId);
        if (!$orderItem) {
            return $this->validationErrorResponse(__('The selected order item does not belong to this order.'));
        }

        $listingId = $orderItem->listing_id;

        $review = ListingReview::updateOrCreate([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'buyer_id' => $request->user()->id,
        ], [
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'listing_id' => $listingId,
            'buyer_id' => $request->user()->id,
            'seller_id' => $orderItem->seller_id,
            'rating' => $request->rating,
            'review' => $request->review,
            'attachments' => [],
            'status' => setting('order_review_approval', 'permission') != 1 ? ListingReviewEnum::Approved : ListingReviewEnum::Pending,
            'reviewed_at' => setting('order_review_approval', 'permission') != 1 ? now() : null,
        ]);

        $uploadedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $uploadedAttachments[] = $this->imageUploadTrait($file, null, 'reviews/');
            }

            // merge with any existing attachments saved earlier
            $existing = (array) $review->attachments;
            $merged = array_values(array_filter(array_merge($existing, $uploadedAttachments)));
            $review->update(['attachments' => $merged]);
            $review->refresh();
        }

        if (setting('order_review_approval', 'permission') != 1) {
            app(ReviewController::class)->listingReviewUpdate($orderItem->listing, $review);
        }

        return $this->successResponse($review, __('Review added successfully.'));
    }

    /**
     * Fetch reviews for an order or a specific order item (buyer API).
     * GET /api/user/orders/{orderNumber}/reviews?order_item_id=123
     */
    public function fetchReviews(Request $request, $orderNumber)
    {
        $request->validate([
            'order_item_id' => ['sometimes', 'integer', 'exists:order_items,id'],
        ]);

        $order = Order::where('buyer_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->with('items.listing')
            ->first();

        if (!$order) {
            return $this->notFoundResponse(__('Order not found.'));
        }

        if ($request->filled('order_item_id')) {
            $orderItemId = (int) $request->input('order_item_id');
            $orderItem = $order->items->firstWhere('id', $orderItemId);
            if (!$orderItem) {
                return $this->validationErrorResponse(__('The selected order item does not belong to this order.'));
            }

            $reviews = ListingReview::where('order_id', $order->id)
                ->where('order_item_id', $orderItemId)
                ->with(['buyer', 'seller', 'listing', 'orderItem'])
                ->get();
        } else {
            $reviews = ListingReview::where('order_id', $order->id)
                ->with(['buyer', 'seller', 'listing', 'orderItem'])
                ->get();
        }

        return $this->successResponse($reviews, __('Reviews fetched successfully.'));
    }

    /* ================================================================
     |  SELLER ORDERS — orders containing items sold by current user
     | ================================================================ */

    public function sellerOrders(Request $request)
    {
        $orders = Order::whereHas('items', fn($q) => $q->where('seller_id', $request->user()->id))
            ->with(['items' => fn($q) => $q->where('seller_id', $request->user()->id), 'items.listing', 'items.bnplLoan.installments', 'buyer', 'bnplItemLoans.installments'])
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->successResponse(
            OrderResource::collection($orders)->response()->getData(true)
        );
    }

    protected function validateStore(Request $request)
    {
    }

    public function bnplItems(Request $request, $orderNumber)
    {
        $order = Order::where('buyer_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->with(['bnplItemLoans.installments'])
            ->first();

        throw_if(!$order, fn() => ValidationException::withMessages(['order_number' => __('Order not found.')]));

        return $this->successResponse(
            BnplItemLoanResource::make($order->bnplItemLoans),
            __('BNPL item details fetched successfully.')
        );
    }

    public function payBnplInstallment(PayBnplInstallmentRequest $request, $orderNumber, $orderItemId, $installmentId)
    {
        $order = Order::where('buyer_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->first();

        throw_if(!$order, fn() => ValidationException::withMessages(['order_number' => __('Order not found.')]));

        $paymentMode = strtolower((string) $request->input('payment_mode', 'balance'));


        if ($paymentMode === 'gateway') {
            if (!$request->filled('gateway_code')) {
                return $this->errorResponse(__('A payment gateway is required.'), 422);
            }

            $gateway = DepositMethod::where('gateway_code', $request->gateway_code)->first();
            if (!$gateway) {
                return $this->errorResponse(__('Invalid payment gateway.'), 422);
            }

            $isManual = $gateway->type === 'manual';

            try {
                $transaction = orderService()->createBnplInstallmentGatewayTransaction(
                    $request->user(),
                    $order,
                    (int) $orderItemId,
                    (int) $installmentId,
                    $gateway->gateway_code,
                    processManualDepositData($request, 'customFields') ?? []
                );
            } catch (\Exception $e) {
                return $this->errorResponse($e->getMessage(), 422);
            }

            if ($isManual) {
                $this->notifyManualBnplInstallmentRequest($transaction->loadMissing('user'));
            }

            $gatewayResponse = $this->depositAutoGateway($gateway->gateway_code, $transaction);

            if ($gatewayResponse instanceof RedirectResponse) {
                $order = $order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']);

                return $this->successResponse([
                    'order' => new OrderResource($order),
                    'payment_url' => $isManual ? null : $gatewayResponse->getTargetUrl(),
                ], __($isManual ? 'Waiting for payment approval.' : 'Redirect to payment gateway.'));
            }

            $order = $order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']);

            return $this->successResponse([
                'order' => new OrderResource($order),
                'payment_url' => null,
            ], __('Payment request created. Wait for admin approval.'));
        }

        try {
            orderService()->payBnplInstallment(
                $request->user(),
                $order,
                (int) $orderItemId,
                (int) $installmentId
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        $order = $order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']);

        return $this->successResponse(
            [
                'order' => new OrderResource($order),
                'payment_url' => null,
            ],
            __('BNPL installment paid successfully.')
        );
    }

    public function bnplInstallments(Request $request, $orderNumber, $orderItemId)
    {
        $order = Order::where('buyer_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $loan = $order->bnplItemLoans()
            ->where('order_item_id', $orderItemId)
            ->with('installments')
            ->firstOrFail();

        return $this->successResponse(
            BnplInstallmentResource::collection($loan->installments),
            __('BNPL installments fetched successfully.')
        );
    }

    public function cancel(Request $request, $orderNumber)
    {
        $order = Order::where('buyer_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'transaction'])
            ->first();

        if (!$order) {
            return $this->notFoundResponse(__('Order not found.'));
        }

        if ($order->status !== OrderStatus::Pending->value) {
            return $this->validationErrorResponse(__('Only pending orders can be cancelled.'));
        }

        orderService()->setOrderCancelled($order, false);

        return $this->successResponse(
            new OrderResource($order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments'])),
            __('Order cancelled successfully.')
        );
    }

    /* ================================================================
     |  PLACE ORDER
     |
     |  Expected JSON body:
     |  {
     |    "items": [
     |      { "listing_id": 1, "quantity": 2, "selected_attributes": [5, 8] }
     |    ],
     |    "coupon_code":           "SAVE10",          // optional
     |    "shipping_address":      "123 Main St",     // optional
    |    "save_shipping_address": true,               // optional
     |    "shipping_charge_amount": 5.00,             // optional
     |    "shipping_charge_type":  "fixed",           // optional
     |    "gateway_code":          "Stripe"           // required
     |  }
     | ================================================================ */

    public function store(PlaceOrderRequest $request)
    {

        $user = $request->user();
        $paymentMode = strtolower((string) $request->input('payment_mode', 'gateway'));
        $isBnpl = $paymentMode === 'bnpl';

        // Gateway already validated by the form request (except BNPL).
        $gateway = $isBnpl ? null : DepositMethod::where('gateway_code', $request->gateway_code)->first();
        $isManualGateway = $gateway?->type === 'manual';

        $service = orderService();

        // Build the data array for OrderService
        $orderData = [
            'items' => $request->items,
            'coupon_code' => $request->coupon_code,
            'shipping_address' => $request->shipping_address,
            'shipping_charge_amount' => $request->shipping_charge_amount ?? 0,
            'shipping_charge_type' => $request->shipping_charge_type ?? 'fixed',
            'gateway_code' => $isBnpl ? 'bnpl' : ucwords($gateway?->gateway_code ?? $request->gateway_code),
            'is_topup' => false,
            'is_bnpl' => $isBnpl,
        ];

        try {
            $order = $service->create($orderData, $request);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }

        if (!$order) {
            return $this->errorResponse(__('Could not create order.'), 500);
        }

        if ($request->boolean('save_shipping_address') && filled($request->shipping_address)) {
            $this->storeShippingAddressFromOrderRequest($request);
        }

        $order = $order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']);

        // --- BNPL purchase (credit based, single item only) ---
        if ($isBnpl) {
            try {
                $service->processBnplOrder($order, $user, $request->integer('split_id'));
                $paidOrder = $service->orderPaymentSuccess($order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']), false);
            } catch (\Exception $e) {
                $service->setOrderFailed($order, false);

                return $this->errorResponse($e->getMessage(), 422);
            }

            if (!isset($paidOrder) || !$paidOrder) {
                return $this->errorResponse(__('Could not complete BNPL order.'), 422);
            }

            $order = $order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']);

            $isDigitalProduct = $order->items->first()->listing->type === ListingType::DIGITAL;
            if ($isDigitalProduct) {
                $service->orderDeliveryWithNotify($order);
            }

            return $this->successResponse(
                [
                    'order' => new OrderResource($order),
                    'payment_url' => null,
                ],
                __('Purchase successful!')
            );
        }

        // --- Instant payment via balance ---
        if ($request->gateway_code === 'balance') {
            if ($user->balance < $order->total_price) {
                $service->setOrderFailed($order, false);

                return $this->errorResponse(__('Insufficient balance.'), 422);
            }

            $order->buyer()->decrement('balance', $order->total_price);
            $service->orderPaymentSuccess($order, false);

            $order = $order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']);

            $isDigitalProduct = $order->isAllItemsDigital;
            if ($isDigitalProduct) {
                $service->orderDeliveryWithNotify($order);
            }

            return $this->successResponse(
                [
                    'order' => new OrderResource($order),
                    'payment_url' => null,
                ],
                __('Purchase successful!')
            );
        }

        // --- External gateway: return payment URL / data for the client ---
        $order->transaction->order = $order;
        $order->transaction->listing = $order->listing;

        $gatewayResponse = $this->depositAutoGateway($gateway->gateway_code, $order->transaction);
        // If the gateway helper returns a redirect, extract the URL for the API client
        if (!$isManualGateway && $gatewayResponse instanceof RedirectResponse) {
            return $this->successResponse([
                'order' => new OrderResource($order),
                'payment_url' => $isManualGateway ? null : $gatewayResponse->getTargetUrl(),
            ], __($isManualGateway ? 'Waiting for payment approval.' : 'Redirect to payment gateway.'));
        }

        // Some gateways may return a view or other response — wrap it
        return $this->successResponse([
            'order' => new OrderResource($order),
            'payment_url' => null,
        ], __('Order created. Wait for admin approval.'));
    }

    /* ================================================================
     |  TOPUP  (API — no session)
     | ================================================================ */

    public function topup(TopupRequest $request)
    {
        $gateway = DepositMethod::where('gateway_code', $request->gateway_code)->first();

        $isManualGateway = $gateway->type === 'manual';

        $service = orderService();

        $orderData = [
            'items' => [],
            'is_topup' => true,
            'topup_amount' => $request->amount,
            'gateway_code' => $gateway->gateway_code,
        ];

        try {
            $order = $service->create($orderData, $request);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }

        if (!$order) {
            return $this->errorResponse(__('Could not create topup order.'), 500);
        }

        $order = $order->refresh()->load(['items', 'transaction']);

        $order->transaction->order = $order;
        $order->transaction->listing = $order->listing;

        $gatewayResponse = $this->depositAutoGateway($gateway->gateway_code, $order->transaction);

        if ($gatewayResponse instanceof RedirectResponse) {
            return $this->successResponse([
                'order' => new OrderResource($order),
                'payment_url' => $isManualGateway ? null : $gatewayResponse->getTargetUrl(),
            ], __($isManualGateway ? 'Waiting for payment approval.' : 'Redirect to payment gateway.'));
        }

        return $this->successResponse([
            'order' => new OrderResource($order),
        ], __('Topup order created. Complete payment via the gateway.'));
    }

    private function notifyManualBnplInstallmentRequest(Transaction $transaction): void
    {
        $status = 'Pending';

        $shortcodes = [
            '[[amount]]' => $transaction->amount,
            '[[charge]]' => $transaction->charge,
            '[[currency]]' => setting('site_currency', 'global'),
            '[[gateway]]' => $transaction->gateway,
            '[[request_at]]' => date('d M, Y h:i A'),
            '[[total_amount]]' => $transaction->final_amount,
            '[[request_link]]' => route('admin.deposit.manual.pending'),
            '[[site_title]]' => setting('site_title', 'global'),
        ];

        $this->sendNotify(setting('support_email', 'global'), 'manual_deposit_request', 'Admin', $shortcodes, null, null, route('admin.deposit.manual.pending'));
    }

    private function storeShippingAddressFromOrderRequest(Request $request): void
    {
        $rawShippingAddress = $request->input('shipping_address');

        if (is_array($rawShippingAddress)) {
            $addressText = trim((string) ($rawShippingAddress['address'] ?? ''));
        } else {
            $addressText = trim((string) $rawShippingAddress);
        }

        if ($addressText === '') {
            return;
        }

        $user = $request->user();

        $alreadySaved = ShippingAddress::query()
            ->where('user_id', $user->id)
            ->where('address', $addressText)
            ->exists();

        if ($alreadySaved) {
            return;
        }

        $isFirstAddress = !ShippingAddress::query()
            ->where('user_id', $user->id)
            ->exists();

        ShippingAddress::query()->create([
            'user_id' => $user->id,
            'type' => 'home',
            'full_name' => trim((string) ($user->name ?? 'Customer')),
            'phone' => trim((string) ($user->phone ?? 'N/A')),
            'address' => $addressText,
            'landmark' => null,
            'region' => null,
            'city' => 'N/A',
            'postal_code' => null,
            'is_default' => $isFirstAddress,
        ]);
    }

    public function upcomingBnplInstallments(Request $request)
    {
        $user = $request->user();

        $from = now();
        $to = now()->addDays(7)->endOfDay();

        $installments = UpcomingInstallmentResource::collection(
            BnplInstallment::query()
                ->whereHas('loan', fn($q) => $q->where('user_id', $user->id))
                ->whereIn('status', ['pending', 'overdue'])
                ->whereBetween('due_at', [$from, $to])
                ->with([
                    'loan' => fn($q) => $q
                        ->withCount('installments')
                        ->with(['orderItem.order', 'orderItem.listing:id,product_name']),
                ])
                ->orderBy('due_at')
                ->paginate($request->per_page ?? 15)
        );

        return $this->successResponse(data: $installments, message: __('Upcoming BNPL installments fetched successfully.'), meta: [
            'current_page' => $installments->currentPage(),
            'last_page' => $installments->lastPage(),
            'per_page' => $installments->perPage(),
            'total' => $installments->total(),
        ]);
    }
}
