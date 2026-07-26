<?php

namespace App\Http\Controllers\Backend;

use App\Enums\BnplLoanStatus;
use App\Enums\ListingReview as ListingReviewEnum;
use App\Enums\ListingType;
use App\Enums\OrderStatus;
use App\Enums\TxnStatus;
use App\Http\Controllers\Controller;
use App\Models\BnplInstallment;
use App\Models\CourierPartner;
use App\Models\ListingReview;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->perPage ?? 15;
        $search = $request->search ?? null;
        $status = $request->status ?? 'all';
        $orders = Order::search($search)
            ->status($status)
            ->when($request->has('type'), function ($query) use ($request) {
                match ($request->type) {
                    'bnpl' => $query->where('is_bnpl', true),
                    'non-bnpl' => $query->where('is_bnpl', false),
                    default => null,
                };
            })
            ->when(in_array($request->input('sort_field'), ['created_at', 'total_price', 'order_number']), function ($query) use ($request) {
                $query->orderBy($request->input('sort_field'), $request->input('sort_dir'));
            }, function ($query) {
                $query->latest();
            })
            ->paginate($perPage);

        return view('backend.order.index', compact('orders'));
    }

    public function show(Order $order)
    {
        abort_if(!$order, 404);
        $order = $order->load(['items.listing', 'items.seller', 'transaction', 'bnplItemLoans.orderItem.listing', 'bnplItemLoans.installments', 'courierPartner']);

        $paymentStatus = TxnStatus::cases();
        $orderStatus = OrderStatus::cases();
        $hasPhysicalItems = $order->items->contains(fn($item) => $item->listing?->type === ListingType::PHYSICAL);
        $courierPartners = CourierPartner::active()->orderBy('name')->get();

        return view('backend.order.show', compact('order', 'paymentStatus', 'orderStatus', 'hasPhysicalItems', 'courierPartners'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        abort_if(!$order, 404);
        abort_if(!$request->user()->can('order-update'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(collect(OrderStatus::cases())->pluck('value')->all())],
            'payment_status' => ['required', Rule::in(collect(TxnStatus::cases())->pluck('value')->all())],
        ]);

        $order->update([
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
        ]);

        if ($validated['status'] === OrderStatus::Delivered->value) {
            $order->update(['delivered_at' => now()]);
            $service = orderService();
            $service->allItemsDeliveryNotify($order);
        } elseif ($order->delivered_at && !in_array($validated['status'], [OrderStatus::Completed->value])) {
            $order->update(['delivered_at' => null]);
        }

        $order->transaction->update([
            'status' => $validated['payment_status'],
        ]);
        $service = $service ?? orderService();
        if ($validated['payment_status'] == TxnStatus::Success->value && $order->status != OrderStatus::Success->value) {
            $service->orderPaymentSuccess($order, false);
        }
        if ($validated['status'] == OrderStatus::Cancelled->value) {
            $service->setOrderCancelled($order, false);
        } elseif ($validated['status'] == OrderStatus::Failed->value) {
            $service->setOrderFailed($order, false);
        } elseif ($validated['status'] == OrderStatus::Refunded->value && $order->status != OrderStatus::Refunded->value) {
            $service->setOrderRefunded($order, false, true);
        } elseif ($validated['status'] == OrderStatus::WaitingForDelivery->value) {
            $service->orderWaitingForDeliveryNotify($order);
        }

        notify()->success(__('Order Status Updated Successfully'));

        return back();
    }

    public function updateDelivery(Request $request, Order $order)
    {
        abort_if(!$order, 404);
        abort_if(!$request->user()->can('order-update'), 403);

        $hasPhysicalItems = $order->items()->whereHas('listing', function ($query) {
            $query->where('type', ListingType::PHYSICAL->value);
        })->exists();

        if (!$hasPhysicalItems) {
            notify()->error(__('Delivery details can only be updated for physical orders.'));

            return back();
        }

        $request->merge([
            'courier_partner_id' => $request->filled('courier_partner_id') ? (int) $request->input('courier_partner_id') : null,
            'estimated_delivery_from' => $request->filled('estimated_delivery_from') ? $request->input('estimated_delivery_from') : null,
            'estimated_delivery_to' => $request->filled('estimated_delivery_to') ? $request->input('estimated_delivery_to') : null,
            'tracking_number' => $request->filled('tracking_number') ? trim((string) $request->input('tracking_number')) : null,
            'tracking_link' => $request->filled('tracking_link') ? trim((string) $request->input('tracking_link')) : null,
            'delivery_note' => $request->filled('delivery_note') ? trim((string) $request->input('delivery_note')) : null,
        ]);

        $validated = $request->validate([
            'courier_partner_id' => ['nullable', 'integer', 'exists:courier_partners,id'],
            'estimated_delivery_from' => ['nullable', 'date', 'required_with:estimated_delivery_to'],
            'estimated_delivery_to' => ['nullable', 'date', 'required_with:estimated_delivery_from', 'after_or_equal:estimated_delivery_from'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_link' => ['nullable', 'url', 'max:500'],
            'delivery_note' => ['nullable', 'string'],
        ]);

        $order->update([
            'courier_partner_id' => $validated['courier_partner_id'] ?? null,
            'estimated_delivery_from' => $validated['estimated_delivery_from'] ?? null,
            'estimated_delivery_to' => $validated['estimated_delivery_to'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'tracking_link' => $validated['tracking_link'] ?? null,
            'delivery_note' => $validated['delivery_note'] ?? null,
        ]);

        notify()->success(__('Order Delivery Updated Successfully'));

        return back();
    }

    public function updateBnplInstallment(Request $request, Order $order, BnplInstallment $installment)
    {
        abort_if(!$request->user()->can('order-update'), 403);

        $loan = $installment->loan()->with('installments')->firstOrFail();
        abort_if((int) $loan->order_id !== (int) $order->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'paid', 'partial', 'overdue', 'cancelled'])],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $request, $installment, $loan) {
            $lockedLoan = $loan->newQuery()->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $lockedInstallment = $installment->newQuery()->whereKey($installment->id)->lockForUpdate()->firstOrFail();

            $nextStatus = (string) $validated['status'];
            $paidAmount = array_key_exists('paid_amount', $validated)
                ? min((float) $lockedInstallment->total_due_amount, max(0, (float) $validated['paid_amount']))
                : (float) $lockedInstallment->paid_amount;

            if ($nextStatus === BnplLoanStatus::Paid->value && $paidAmount <= 0) {
                $paidAmount = (float) $lockedInstallment->total_due_amount;
            }

            if ($nextStatus !== BnplLoanStatus::Paid->value && !$request->filled('paid_amount') && $lockedInstallment->status === BnplLoanStatus::Paid->value) {
                $paidAmount = 0;
            }

            $lockedInstallment->update([
                'status' => $nextStatus,
                'paid_amount' => $paidAmount,
                'paid_at' => $nextStatus === BnplLoanStatus::Paid->value ? ($lockedInstallment->paid_at ?? now()) : null,
            ]);

            $paidPrincipal = $lockedLoan->installments()
                ->where('status', BnplLoanStatus::Paid->value)
                ->when((float) $lockedLoan->initial_paid_amount > 0, fn($query) => $query->where('installment_no', '>', 1))
                ->sum('principal_amount');

            $newOutstanding = max(0, round((float) $lockedLoan->final_amount_to_pay - (float) $paidPrincipal, 2));
            $oldOutstanding = (float) $lockedLoan->remaining_due_amount;
            $releasedPrincipal = round($oldOutstanding - $newOutstanding, 2);

            $hasPendingInstallments = $lockedLoan->installments()
                ->whereNotIn('status', [BnplLoanStatus::Paid->value, BnplLoanStatus::Cancelled->value])
                ->exists();

            $hasOverdueInstallments = $lockedLoan->installments()->where('status', BnplLoanStatus::Overdue->value)->exists();
            $hasProcessingInstallments = $lockedLoan->installments()->where('status', BnplLoanStatus::Processing->value)->exists();

            $loanStatus = match (true) {
                !$hasPendingInstallments => BnplLoanStatus::Paid->value,
                $hasOverdueInstallments => BnplLoanStatus::Overdue->value,
                $hasProcessingInstallments => BnplLoanStatus::Processing->value,
                $paidPrincipal > 0 || (float) $lockedLoan->initial_paid_amount > 0 => BnplLoanStatus::PartiallyPaid->value,
                default => BnplLoanStatus::Pending->value,
            };

            $lockedLoan->update([
                'remaining_due_amount' => $newOutstanding,
                'status' => $loanStatus,
            ]);

            if ($releasedPrincipal !== 0.0) {
                $buyer = $lockedLoan->user()->lockForUpdate()->first();
                if ($buyer) {
                    $creditLimit = max(0, round((float) $buyer->credit_limit_amount, 2));
                    $nextUsed = round((float) $buyer->used_credit_limit_amount - $releasedPrincipal, 2);
                    $nextUsed = min($creditLimit, max(0, $nextUsed));
                    $nextRemaining = max(0, round($creditLimit - $nextUsed, 2));

                    $buyer->update([
                        'used_credit_limit_amount' => $nextUsed,
                        'remaining_credit_limit_amount' => $nextRemaining,
                    ]);
                }
            }
        });

        notify()->success(__('BNPL installment updated successfully.'));

        return back();
    }

    /**
     * Admin: Add or update a review for an order item
     */
    public function postListingReview(Request $request, Order $order)
    {
        abort_if(!$request->user()->can('order-update'), 403);

        $validated = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $order->load('items');

        // ensure order item belongs to this order
        $orderItem = $order->items->firstWhere('id', $validated['order_item_id']);
        if (!$orderItem) {
            notify()->error(__('The selected order item does not belong to this order.'));

            return back()->withInput();
        }

        $review = ListingReview::updateOrCreate([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'buyer_id' => $order->buyer_id,
        ], [
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'listing_id' => $orderItem->listing_id,
            'buyer_id' => $order->buyer_id,
            'seller_id' => $orderItem->seller_id,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
            'status' => setting('order_review_approval', 'permission') != 1 ? ListingReviewEnum::Approved : ListingReviewEnum::Pending,
            'reviewed_at' => setting('order_review_approval', 'permission') != 1 ? now() : null,
        ]);

        if (setting('order_review_approval', 'permission') != 1) {
            app(ReviewController::class)->listingReviewUpdate($orderItem->listing, $review);
        }

        notify()->success(__('Review saved successfully.'));

        return back();
    }
}
