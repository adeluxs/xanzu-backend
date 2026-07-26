<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function validateAndCalculate(string $couponCode, float $subtotal): array
    {
        $coupon = Coupon::where('code', $couponCode)->first();

        throw_if(! $coupon, fn () => ValidationException::withMessages(['coupon_code' => __('Invalid coupon code.')]));
        throw_if($coupon->expires_at->isPast(), fn () => ValidationException::withMessages(['coupon_code' => __('Coupon has expired.')]));
        throw_if($coupon->max_use_limit <= $coupon->total_used, fn () => ValidationException::withMessages(['coupon_code' => __('Coupon usage limit exceeded.')]));
        throw_if(! ($coupon->status ?? 1), fn () => ValidationException::withMessages(['coupon_code' => __('Coupon is inactive.')]));

        $discountAmount = $coupon->discount_type === 'percentage'
            ? round(($coupon->discount_value / 100) * $subtotal, 2)
            : $coupon->discount_value;

        return [
            'coupon_id' => $coupon->id,
            'discount_amount' => $discountAmount,
        ];
    }
}
