<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'listing_id',
        'seller_id',
        'category_id',
        'plan_id',
        'is_topup',
        'product_name', // for outside purchase
        'product_image', // for outside purchase
        'quantity',
        'org_unit_price',
        'unit_price',
        'total_price',
        'selected_attributes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'org_unit_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'selected_attributes' => 'array',
            'is_topup' => 'boolean',
        ];
    }

    protected $appends = ['status_badge'];

    /* ───────── Relationships ───────── */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }


    public function bnplLoan()
    {
        return $this->hasOne(BnplItemLoan::class, 'order_item_id');
    }

    /* ───────── Accessors ───────── */

    protected function statusBadge(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return match ($this->status) {
                OrderStatus::Pending->value => '<span class="badge rounded-pill pending">Pending</span>',
                OrderStatus::Success->value => '<span class="badge rounded-pill success">Success</span>',
                OrderStatus::WaitingForDelivery->value => '<span class="badge badge-2 rounded-pill bg-primary">Waiting For Delivery</span>',
                OrderStatus::Completed->value => '<span class="badge rounded-pill success">Delivered</span>',
                OrderStatus::Delivered->value => '<span class="badge rounded-pill success">Delivered</span>',
                OrderStatus::Cancelled->value => '<span class="badge rounded-pill error">Cancelled</span>',
                OrderStatus::Failed->value => '<span class="badge rounded-pill error">Failed</span>',
                OrderStatus::Refunded->value => '<span class="badge rounded-pill error">Refunded</span>',
                default => '<span class="badge rounded-pill bg-warning">Pending</span>',
            };
        });
    }

    public function review()
    {
        return $this->hasOne(ListingReview::class, 'order_item_id');
    }

    /* ───────── View helpers ───────── */

    public function hasDiscount(): bool
    {
        if ($this->is_topup) {
            return false;
        }

        return (float) $this->org_unit_price > (float) $this->unit_price;
    }

    public function discountAmount(): float
    {
        return max(0, (float) $this->org_unit_price - (float) $this->unit_price);
    }

    public function displayUnitPrice(): string
    {
        $currency = setting('site_currency', 'global');

        if ($this->is_topup) {
            return amountWithCurrency((float) $this->total_price, $currency);
        }

        return amountWithCurrency((float) $this->unit_price, $currency);
    }

    public function displayOriginalPrice(): ?string
    {
        if (! $this->hasDiscount()) {
            return null;
        }

        return amountWithCurrency((float) $this->org_unit_price, setting('site_currency', 'global'));
    }
}
