<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositMethod extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = [
        'gateway_logo',
    ];

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class, 'gateway_id');
    }

    protected function scopeCode($query, $code)
    {
        return $query->where('gateway_code', $code);
    }

    protected function gatewayLogo(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->logo == null) {
                return asset($this->gateway->logo);
            }

            return asset($this->logo);
        });
    }

    public function casts()
    {
        return [
            'field_options' => 'json',
        ];
    }
}
