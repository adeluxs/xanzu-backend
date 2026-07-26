<?php

namespace App\Models;

use App\Enums\ListingType;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'order_number',
        'coupon_id',
        'subtotal',
        'discount_amount',
        'shipping_charge_amount',
        'shipping_charge_type',
        'final_shipping_charge',
        'total_price',
        'status',
        'payment_status',
        'gateway_id',
        'transaction_id',
        'shipping_address',
        'estimated_delivery_from',
        'estimated_delivery_to',
        'courier_partner_id',
        'tracking_number',
        'tracking_link',
        'delivery_note',
        'order_date',
        'is_bnpl',
        'bnpl_upfront_amount',
        'delivered_at'
    ];

    protected $appends = ['status_badge'];

    /* ───────── Relationships ───────── */

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Backward-compatible: first item's listing (for single-item orders / topup).
     */
    public function listing()
    {
        return $this->hasOneThrough(
            Listing::class,
            OrderItem::class,
            'order_id',  // FK on order_items
            'id',        // FK on listings
            'id',        // local key on orders
            'listing_id' // local key on order_items
        );
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'order_id')->latest();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'order_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Backward-compatible: returns the seller from the first order item.
     */
    public function seller()
    {
        return $this->hasOneThrough(
            User::class,
            OrderItem::class,
            'order_id',
            'id',
            'id',
            'seller_id'
        );
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function courierPartner()
    {
        return $this->belongsTo(CourierPartner::class, 'courier_partner_id');
    }

    public function bnplItemLoans()
    {
        return $this->hasOne(BnplItemLoan::class, 'order_id', 'id');
    }

    /* ───────── Accessors ───────── */

    /**
     * Whether this order is a topup (all items are topup).
     */
    protected function isTopup(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->items->every(fn($item) => $item->is_topup);
        });
    }

    /**
     * Total quantity across all items.
     */
    protected function quantity(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->items->sum('quantity');
        });
    }

    /**
     * Backward-compat: first item's seller_id.
     */
    protected function sellerId(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->items->first()?->seller_id;
        });
    }

    /**
     * Backward-compat: custom_fields no longer on orders table.
     * Returns null so views don't error.
     */
    protected function customFields(): Attribute
    {
        return Attribute::make(get: function () {
            return null;
        });
    }

    /**
     * Get the status badge
     */
    protected function statusBadge(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return match ($this->status) {
                OrderStatus::Pending->value => '<span class="badge rounded-pill pending">Pending</span>',
                OrderStatus::Success->value => '<span class="badge rounded-pill success">Success</span>',
                OrderStatus::WaitingForDelivery->value => '<span class="badge badge-2 rounded-pill bg-primary">Waiting For Delivery</span>',
                OrderStatus::Delivered->value => '<span class="badge rounded-pill success">Delivered</span>',
                OrderStatus::Completed->value => '<span class="badge rounded-pill success">Completed</span>',
                OrderStatus::Cancelled->value => '<span class="badge rounded-pill error">Cancelled</span>',
                OrderStatus::Failed->value => '<span class="badge rounded-pill error">Failed</span>',
                OrderStatus::Refunded->value => '<span class="badge rounded-pill error">Refunded</span>',
                default => '<span class="badge rounded-pill bg-warning">Pending</span>',
            };
        });
    }

    public function deliveryItem()
    {
        return $this->hasMany(DeliveryItem::class, 'order_id');
    }

    /* ───────── Scopes ───────── */

    protected function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('order_number', 'like', '%' . $search . '%');
        }

        return $query;
    }

    public function review()
    {
        return $this->hasOne(ListingReview::class, 'order_id');
    }

    protected function scopeStatus($query, $status)
    {
        if ($status && $status != 'all') {
            return $query->where('status', $status);
        }

        return $query;
    }

    /* ───────── Helpers ───────── */

    /**
     * Get all unique seller IDs from order items.
     */
    public function getSellerIds(): array
    {
        return $this->items->pluck('seller_id')->unique()->filter()->values()->toArray();
    }

    /**
     * Get product names from all items.
     */
    public function getProductNames(): string
    {
        return $this->items
            ->map(fn($item) => $item->listing?->product_name ?? $item->product_name)
            ->filter()
            ->implode(', ');
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_charge_amount' => 'decimal:2',
            'final_shipping_charge' => 'decimal:2',
            'total_price' => 'decimal:2',
            'bnpl_upfront_amount' => 'decimal:2',
            'is_bnpl' => 'boolean',
            'shipping_address' => 'array',
            'estimated_delivery_from' => 'datetime',
            'estimated_delivery_to' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }


    public function totalInstallments(): int
    {
        return $this?->bnplItemLoans?->installments?->count() ?? 0;
    }

    public function paidInstallments(): int
    {
        return $this?->bnplItemLoans?->installments?->where('status', 'paid')?->count() ?? 0;
    }

    public function isActiveBnpl(): bool
    {
        return $this->is_bnpl && $this->paidInstallments() < $this->totalInstallments();
    }

    public function estimatedDelivery()
    {
        if ($this->estimated_delivery_from && $this->estimated_delivery_to) {

            // if both date same
            if ($this->estimated_delivery_from->isSameDay($this->estimated_delivery_to)) {
                return $this->estimated_delivery_from->format('M d, Y');
            }
            return $this->estimated_delivery_from->format('M d, Y') . ' - ' . $this->estimated_delivery_to->format('M d, Y');
        } elseif ($this->estimated_delivery_from) {
            return 'From ' . $this->estimated_delivery_from->format('M d, Y');
        } elseif ($this->estimated_delivery_to) {
            return 'By ' . $this->estimated_delivery_to->format('M d, Y');
        }
        return null;
    }

    public function bnplCheckoutSession()
    {
        return $this->hasOne(BnplCheckoutSession::class, 'order_id');
    }

    /**
     * Get the isAllItemsDigital attribute  
     *
     * @param  string  $value
     * @return string
     */
    public function getIsAllItemsDigitalAttribute($value)
    {
        return $this->items->every(fn($item) => $item->listing?->type === ListingType::DIGITAL);
    }

    // is all items physical
    public function getIsAllItemsPhysicalAttribute($value)
    {
        return $this->items->every(fn($item) => $item->listing?->type === ListingType::PHYSICAL);
    }

    //  has physical item
    public function getHasPhysicalItemAttribute($value)
    {
        return $this->items->contains(fn($item) => $item->listing?->type === ListingType::PHYSICAL);
    }
    // has digital item
    public function getHasDigitalItemAttribute($value)
    {
        return $this->items->contains(fn($item) => $item->listing?->type === ListingType::DIGITAL);
    }

    /* ───────── View helpers ───────── */

    /**
     * Unique sellers (User) across all items, eager-loaded.
     */
    public function uniqueSellers()
    {
        return $this->items()
            ->with('seller')
            ->get()
            ->pluck('seller')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Whether the order has reached a terminal delivered/completed state.
     */
    public function isDeliveredOrCompleted(): bool
    {
        return in_array($this->status, [
            OrderStatus::Delivered->value,
            OrderStatus::Completed->value,
        ], true);
    }

    /**
     * Comma-joined delivered item data (codes/keys already attached to this order).
     */
    public function deliveredItemsText(): string
    {
        return $this->deliveryItem->pluck('data')->filter()->values()->implode(', ');
    }

    /**
     * Distinct listings still waiting for delivery, with their order item.
     */
    public function waitingDeliveryListings()
    {
        return $this->items()
            ->with('listing')
            ->where('status', OrderStatus::WaitingForDelivery->value)
            ->get()
            ->filter(fn ($item) => $item->listing !== null)
            ->unique('listing_id')
            ->values();
    }

    /**
     * Review attached to the first order item (admin review shortcut).
     */
    public function firstItemReview()
    {
        return $this->items->first(fn ($item) => $item->review !== null)?->review
            ?? $this->items->first()?->review;
    }
}
