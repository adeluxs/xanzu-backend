<?php

namespace App\Models;

use App\Enums\ListingReview as ListingReviewStatus;
use App\Enums\ListingType;
use App\Traits\ModelIdEncDec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;
    use ModelIdEncDec;

    protected $fillable = [
        'seller_id',
        'category_id',
        'brand_id',
        'provider_id',
        'provider_product_id',
        'product_url',
        'product_name',
        'description',
        'price',
        'discount_type',
        'discount_value',
        'quantity',
        'thumbnail',
        'delivery_method',
        'delivery_speed_unit',
        'delivery_speed',
        'shipping_charge',
        'shipping_charge_type',
        'status',
        'slug',
        'sold_count',
        'is_approved',
        'is_trending',
        'is_flash',
        'avg_rating',
        'subcategory_id',
        'custom_fields',
        'type', // digital or physical
        'has_attributes',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'is_approved' => 'boolean',
        'is_trending' => 'boolean',
        'is_flash' => 'boolean',
        'has_attributes' => 'boolean',
        'avg_rating' => 'float',
        'shipping_charge' => 'decimal:2',
        'type' => ListingType::class,
    ];

    protected $appends = [
        'final_price',
        'status_badge',
        'thumbnail_url',
        'average_rating',
        'total_reviews',
    ];

    public function images()
    {
        return $this->hasMany(ListingGallery::class, 'listing_id');
    }

    public function listingAttributes()
    {
        return $this->hasMany(ListingAttribute::class, 'listing_id');
    }

    /**
     * Get grouped attributes for display.
     */
    public function getGroupedAttributes()
    {
        return $this->listingAttributes->groupBy('group');
    }

    public function hasDiscount(): Attribute
    {
        return Attribute::make(get: function ($value) {
            if ($this->has_attributes && $this->listingAttributes->isNotEmpty()) {
                return $this->listingAttributes->where('discount_amount', '>', 0)->isNotEmpty();
            }

            return $this->discount_value > 0;
        });
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Scope a query to only include active
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the url
     *
     * @param  string  $value
     * @return string
     */
    protected function URL(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return route('listing.details', [
                'slug' => $this->slug,
            ]);
        });
    }

    /**
     * Get the final_price
     *
     * @param  string  $value
     * @return string
     */
    protected function finalPrice(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return $this->discount_type == 'percentage'
                ? $this->price - ($this->price * $this->discount_value / 100)
                : $this->price - $this->discount_value;
        });
    }

    protected function discountAmount(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return $this->discount_type == 'percentage' ? $this->price * $this->discount_value / 100 : $this->discount_value;
        });
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return asset($this->thumbnail);
        });
    }

    /**
     * Get the statusBadge
     *
     * @param  string  $value
     * @return string
     */
    protected function statusBadge(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return match ($this->status) {
                'draft' => '<span class="badge rounded-pill bg-warning text-black">Draft</span>',
                'active' => '<span class="badge rounded-pill bg-success">Active</span>',
                'inactive' => '<span class="badge rounded-pill bg-danger">Inactive</span>',
                'pending' => '<span class="badge rounded-pill bg-primary">Pending</span>',
                'rejected' => '<span class="badge rounded-pill bg-danger">Rejected</span>',
                default => '<span class="badge rounded-pill bg-warning">Draft</span>',
            };
        });
    }

    public static function findOwn($id, $columns = ['*'])
    {
        return self::where('id', $id)->where('seller_id', auth()->id())->firstOrFail($columns);
    }

    /**
     * Scope a query to only include search
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeSearch($query, $q)
    {
        return $query->where('product_name', 'LIKE', '%' . $q . '%')
            ->orWhere('description', 'LIKE', '%' . $q . '%');
    }

    /**
     * Scope a query to only include status
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope a query to only include public
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopePublic($query)
    {
        return $query->where('status', 'active')->where('is_approved', 1)
            ->where(function (Builder $q) {
                $q->where('delivery_method', 'auto')->whereHas('deliveryItems', function (Builder $q) {
                    $q->deliveryAble()->whereNull('order_id');
                })->orWhere('delivery_method', 'manual');
            });
    }

    protected function scopePublicWithoutStock($query)
    {
        return $query->where('status', 'active')->where('is_approved', 1);
    }

    public function isOutOfStock(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->has_attributes) {
                $attributes = $this->relationLoaded('listingAttributes')
                    ? $this->listingAttributes
                    : $this->listingAttributes()->get(['qty']);

                if ($attributes->isNotEmpty()) {
                    return $attributes->sum('qty') <= 0;
                }
            }

            if ($this->delivery_method === 'auto') {
                $available = array_key_exists('available_delivery_items_count', $this->attributes)
                    ? (int) $this->attributes['available_delivery_items_count']
                    : $this->deliveryItems()->deliveryAble()->whereNull('order_id')->count();

                return $available <= 0 || $this->quantity <= 0;
            }

            return $this->quantity <= 0;
        });
    }

    public function deliveryItems()
    {
        return $this->hasMany(DeliveryItem::class, 'listing_id');
    }

    public function deliveryItemsNoData()
    {
        return $this->hasMany(DeliveryItem::class, 'listing_id')->whereNull('data');
    }
    public function deliveryItemsUnused()
    {
        return $this->hasMany(DeliveryItem::class, 'listing_id')->whereNull(['order_id'])->where('is_used', 0);
    }

    public function analysis()
    {
        return $this->hasMany(ListingAnalysis::class, 'listing_id');
    }

    public function reviews()
    {
        return $this->hasMany(ListingReview::class, 'listing_id');
    }

    public function approvedReviews()
    {
        return $this->hasMany(ListingReview::class, 'listing_id')->whereNull('parent_id')->where('status', ListingReviewStatus::Approved);
    }

    /**
     * Get average rating attribute
     */
    protected function averageRating(): Attribute
    {
        // avg_rating is already denormalized when reviews are moderated. Avoid
        // a hidden aggregate query every time a Listing model is serialized.
        return Attribute::make(get: fn () => (float) ($this->avg_rating ?? 0));
    }

    /**
     * Get total reviews attribute
     */
    protected function totalReviews(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->approvedReviews()->count();
        });
    }

    /**
     * Scope a query to only include trending
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeTrending($query)
    {
        return $query->where('is_trending', 1);
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, OrderItem::class, 'listing_id', 'id', 'id', 'order_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'listing_id');
    }

    /**
     * Get the admin URL for the listing
     *
     * @return string
     */
    protected function adminUrl(): Attribute
    {
        return Attribute::make(get: function () {
            return route('admin.listing.edit', $this->id);
        });
    }

    public function canReview($userId)
    {
        // check product order count and review count by user
        $orderCount = $this->orders()->where('user_id', $userId)->count();
        $reviewCount = $this->reviews()->where('user_id', $userId)->where('status', ListingReviewStatus::Approved)->count();

        return $orderCount > $reviewCount;
    }


    public function shippingConfig()
    {
        $shipping_charge = $this->shipping_charge;
        $shipping_charge_type = $this->shipping_charge_type;

        if ($this->type === ListingType::DIGITAL || $this->type?->value === 'digital') {
            return [
                'shipping_charge' => 0,
                'shipping_charge_type' => 'fixed',
            ];
        }

        $defaultShippingCharge = setting('shipping_charge', 'fee');
        $defaultShippingChargeType = setting('shipping_charge_type', 'fee');

        if (is_null($shipping_charge) || empty($shipping_charge) || $shipping_charge <= 0) {
            $shipping_charge = $defaultShippingCharge;
        }
        if (is_null($shipping_charge_type) || empty($shipping_charge_type)) {
            $shipping_charge_type = $defaultShippingChargeType;
        }

        return [
            'shipping_charge' => $shipping_charge,
            'shipping_charge_type' => $shipping_charge_type,
        ];
    }
}
