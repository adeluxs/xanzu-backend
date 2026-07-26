<?php

namespace App\Models;

use App\Traits\ModelIdEncDec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    use ModelIdEncDec;

    protected $fillable = [
        'code',
        'is_home',
        'discount_type',
        'discount_value',
        'expires_at',
        'max_use_limit',
        'total_used',
        'seller_id',
        'status',
    ];

    /**
     * Scope a query to only include approved
     *
     * @param  Builder  $query
     * @return Builder
     */
    // admin_approval removed from model

    /**
     * Get the expires_at
     *
     * @param  string  $value
     * @return string
     */
    protected function expiresAt(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return now()->parse($value)->endOfDay();
        });
    }
    protected function isValid(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return $this->expires_at->isFuture() && ($this->max_use_limit === null || $this->total_used < $this->max_use_limit);
        });
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_home' => 'boolean',
        ];
    }
}
