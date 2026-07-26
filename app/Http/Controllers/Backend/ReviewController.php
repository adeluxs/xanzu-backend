<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingReview;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use NotifyTrait;

    public function __construct()
    {
        $this->middleware('can:listing-edit');
    }

    public function index(Request $request)
    {
        $query = ListingReview::with(['listing', 'buyer', 'seller', 'order'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('rating') and $request->rating != 'all', function ($q) use ($request) {
                $q->where('rating', $request->rating);
            })->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('listing', function ($q) use ($request) {
                    $q->where('product_name', 'like', "%{$request->search}%");
                })->orWhereHas('buyer', function ($q) use ($request) {
                    $q->where('username', 'like', "%{$request->search}%");
                })->orWhere('review', 'like', "%{$request->search}%");
            })
            ->when($request->filled('sort_field'), function ($q) use ($request) {
                $q->orderBy($request->sort_field, $request->sort_dir ?? 'asc');
            });

        $reviews = $query->latest()->paginate(20);

        return view('backend.reviews.index', compact('reviews'));
    }

    public function show(ListingReview $review)
    {
        $review->load(['listing', 'buyer', 'seller', 'order']);

        return view('backend.reviews.show', compact('review'));
    }

    public function approve(ListingReview $review)
    {
        $oldStatus = $review->status;

        $review->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $this->listingReviewUpdate($review->listing, $review, $oldStatus == \App\Enums\ListingReview::Pending);

        notify()->success(__('Review approved successfully.'));

        return back();
    }

    public function reject(Request $request, ListingReview $review)
    {
        $request->validate([
            'admin_notes' => ['required', 'string', 'max:500'],
        ]);

        $review->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
            'reviewed_at' => now(),
        ]);

        $this->listingReviewUpdate($review->listing, $review, false);

        notify()->success(__('Review rejected successfully.'));

        return back();
    }

    public function listingReviewUpdate(Listing $listing, ?ListingReview $review = null, $sendMail = true)
    {
        $avgRating = $listing->approvedReviews()->avg('rating');
        $avgRating = is_null($avgRating) ? 0 : $avgRating;
        $listing->update(['avg_rating' => $avgRating]);

        // seller avg_review total_reviews
        $seller = $listing->seller;

        $sellerApprovedReviews = ListingReview::where('seller_id', $seller?->id)->approved();
        $sellerUpdatedData = [
            'avg_rating' => (clone $sellerApprovedReviews)->avg('rating'),
            'total_reviews' => (clone $sellerApprovedReviews)->count(),
        ];

        $seller?->update($sellerUpdatedData);

        // send notification to seller

        if ($sendMail) {
            $shortcodes = [
                '[[seller_name]]' => $seller->name,
                '[[buyer_name]]' => $review->buyer->full_name,
                '[[rating]]' => $review->rating,
                '[[listing_title]]' => $listing->product_name,
                '[[review]]' => $review->review,
                '[[order_number]]' => $review->order->order_number,
                '[[listing_url]]' => route('home'),
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_link]]' => route('home'),
            ];

            $this->sendNotify($seller->email, 'seller_review_received', 'User', $shortcodes, $seller->phone, $seller->id, route('listing.details', $listing->slug));
        }

    }

    public function destroy(ListingReview $review)
    {
        $review->delete();

        notify()->success('Review deleted successfully.');

        return back();
    }
}
