<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'group',
        'label',
        'price',
        'discount_type',
        'discount_amount',
        'final_price',
        'qty',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'qty' => 'integer',
    ];

    /**
     * Boot method to auto-calculate final_price before saving.
     */
    protected static function booted(): void
    {
        static::saving(function (self $attribute) {
            $attribute->final_price = self::calculateFinalPrice(
                (float) $attribute->price,
                $attribute->discount_type,
                (float) $attribute->discount_amount
            );
        });
    }

    /**
     * Calculate the final price based on discount.
     */
    public static function calculateFinalPrice(float $price, ?string $discountType, float $discountAmount): float
    {
        if (! $discountType || $discountAmount <= 0) {
            return $price;
        }

        if ($discountType === 'percentage') {
            return round($price - ($price * $discountAmount / 100), 2);
        }

        return round(max(0, $price - $discountAmount), 2);
    }

    /**
     * Get the listing that owns the attribute.
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
