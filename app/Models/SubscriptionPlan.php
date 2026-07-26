<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the chargeAmount
     *
     * @param  string  $value
     * @return string
     */
    protected function chargeAmount(): Attribute
    {
        return Attribute::make(get: function ($value) {
            if ($this->charge_type == 'fixed') {
                return $this->charge_value;
            }

            return ($this->charge_value / 100) * $this->price;
        });
    }

    /**
     * The attributes that should be cast to native types.
     */
    protected function casts(): array
    {
        return [
            'features' => 'array',
        ];
    }
}
