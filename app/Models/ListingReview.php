<?php

namespace App\Models;

use App\Enums\ListingReview as ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ListingReview extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (self $review) {
            if ($review->parent_id === null && $review->status === ReviewStatus::Approved) {
                self::forgetReviewSummaryCache($review->listing_id);
            }
        });

        static::updated(function (self $review) {
            if (
                $review->parent_id === null
                && $review->wasChanged('status')
                && $review->status === ReviewStatus::Approved
            ) {
                self::forgetReviewSummaryCache($review->listing_id);
            }
        });
    }

    protected $fillable = [
        'listing_id',
        'order_id',
        'buyer_id',
        'seller_id',
        'order_item_id',
        'rating',
        'review',
        'status',
        'reviewed_at',
        'admin_notes',
        'parent_id',
        'flag_reason',
        'attachments',
    ];

    /**
     * Get the listing that owns the review.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function reply()
    {
        return $this->hasOne(ListingReview::class, 'parent_id');
    }

    /**
     * Get the user that wrote the review.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the order associated with the review.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope for approved reviews only.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', ReviewStatus::Approved);
    }

    /**
     * Scope for pending reviews only.
     */
    public function scopePending($query)
    {
        return $query->where('status', ReviewStatus::Pending);
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
            'reviewed_at' => 'datetime',
            'status' => ReviewStatus::class,
            'attachments' => 'array',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    protected static function forgetReviewSummaryCache(?int $listingId): void
    {
        if (!$listingId) {
            return;
        }

        Cache::forget('listing_review_summary:' . $listingId);
        Cache::forget('listing_review_summary:' . $listingId . ':queued');
    }
}
