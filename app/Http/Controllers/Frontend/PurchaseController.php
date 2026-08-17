<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ListingReview as ListingReviewEnum;
use App\Http\Controllers\Backend\ReviewController;
use App\Http\Controllers\Controller;
use App\Models\ListingReview;
use App\Models\Order;
use App\Models\Page;
use App\Support\JsonData;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'sort' => ['nullable', 'in:asc,desc'],
        ]);
        $__orders = Order::whereBelongsTo(auth()->user(), 'buyer')
            ->when($request->sort, function ($query) use ($request) {
                $query->orderBy('total_price', $request->sort);
            })
            ->where('is_topup', false);

        $orders = $__orders->clone()
            ->latest()
            ->paginate(15);

        $count = $__orders->clone()->count();

        return view('frontend::purchase.index', compact('orders', 'count'));
    }

    public function invoice(Order $order)
    {
        abort_if(! $order || $order->buyer_id != auth()->id(), 404);
        $order->load('items.listing', 'transaction');

        return view('frontend::purchase.invoice', compact('order'));
    }

    public function success(Order $order)
    {
        abort_if(! $order || $order->buyer_id != auth()->id(), 404);
        $page = Page::where('code', 'payment-successful')->where('locale', app()->getLocale())
            ->theme()
            ->first();

        if (! $page) {
            $page = Page::where('code', 'payment-successful')->where('locale', defaultLocale())
                ->theme()
                ->firstOrFail();
        }

        $data = new Fluent(JsonData::decodeArray($page->data));

        return view('frontend::purchase.success', compact('order', 'data'));
    }

    public function deliveryItems(Order $order)
    {
        abort_if(! $order || $order->buyer_id != auth()->id(), 404);
        $order->load('deliveryItem');
        $deliveryItems = $order->deliveryItem;

        return view('frontend::purchase.delivery-item', compact('order', 'deliveryItems'));
    }

    public function storeReview(Request $request)
    {
        $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'max:500'],
        ]);

        $order = Order::find($request->order_id);

        if (! $order) {
            notify()->error(__('Order not found!'));

            return back();
        }

        // Review is attached to the first item's listing for backward compat
        $firstItem = $order->items()->first();

        $review = ListingReview::updateOrCreate([
            'order_id' => $order->id,
            'listing_id' => $firstItem?->listing_id,
            'buyer_id' => auth()->id(),
        ], [
            'order_id' => $order->id,
            'listing_id' => $firstItem?->listing_id,
            'buyer_id' => auth()->id(),
            'seller_id' => $firstItem?->seller_id,
            'rating' => $request->rating,
            'review' => $request->review,
            'status' => setting('order_review_approval', 'permission') != 1 ? ListingReviewEnum::Approved : ListingReviewEnum::Pending,
        ]);

        if (setting('order_review_approval', 'permission') != 1) {
            app(ReviewController::class)->listingReviewUpdate($firstItem?->listing, $review);
        }

        notify()->success(__('Review added successfully!'));

        return back();
    }
}
