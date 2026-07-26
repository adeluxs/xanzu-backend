<?php

namespace App\Models;

use App\Enums\GatewayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function scopeCode($query, $code)
    {
        return $query->where('gateway_code', $code);
    }

    protected function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    protected function casts(): array
    {
        return [
            'type' => GatewayType::class,
        ];
    }
}
