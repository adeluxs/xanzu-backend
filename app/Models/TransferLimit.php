<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TransferLimit extends Model
{
    protected $fillable = [
        'user_type',
        'min_amount',
        'max_amount',
        'daily_limit',
        'daily_transaction_count',
        'monthly_limit',
        'monthly_transaction_count',
        'status',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function scopeForUserType(Builder $query, string $userType): Builder
    {
        return $query->whereIn('user_type', [$userType, 'all']);
    }

    public static function getLimitFor(string $userType): ?self
    {
        return static::active()
            ->forUserType($userType)
            ->orderByRaw("CASE WHEN user_type = ? THEN 0 ELSE 1 END", [$userType])
            ->first();
    }
}
