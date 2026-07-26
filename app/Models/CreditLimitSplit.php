<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLimitSplit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    protected function scopeActive($query)
    {
        $query->where('status', 1);
    }

    public function creditLimit()
    {
        return $this->belongsTo(CreditLimit::class);
    }

    public static function splitPromoMessage()
    {
        return cache()->remember('split_promo_message', now()->addDays(7), function () {
            $split = self::oldest('total_split')->active()->first();
            return $split ? __('Split into :total :interestType payments. :interest fees', ['total' => $split->total_split, 'interestType' => $split->interest_rate_amount > 0 ? 'with-interest' : 'interest-free', 'interest' => ($split->interest_rate_amount > 0 ? $split->interest_rate_amount . ($split->interest_rate_type == 'fixed' ? ' ' . setting('site_currency') : '%') : 'No')]) : null;
        });
    }
}
