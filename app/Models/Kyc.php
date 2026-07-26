<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kyc extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Scope a query to only include userWise
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeUserWise($query, $user = null)
    {
        $user = $user ?? auth()->user();
        $is_seller = $user->is_seller;

        return $query->where(function ($q) use ($is_seller) {
            $q->when($is_seller, function ($query) {
                $query->where('user_type', 'merchant')->orWhere('user_type', 'both');
            }, function ($query) {
                $query->where('user_type', 'buyer')->orWhere('user_type', 'both');
            });
        });
    }
}
