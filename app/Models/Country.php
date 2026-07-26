<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'currency_code',
        'dial_code',
        'own_rate',
        'status',
    ];

    public function scopeActive()
    {
        return $this->where('status', 1);
    }

    public function phoneDialCode()
    {
        if ($this->dial_code) {
            return $this->dial_code;
        }
        $country = collect(getCountries())->filter(function ($value) {
            return $value['name'] == $this->name;
        })->first();

        return $country['dial_code'] ?? '';
    }

    protected function casts()
    {
        return [
            'status' => 'boolean',
            'min_transfer_limit' => 'decimal:2',
            'max_transfer_limit' => 'decimal:2',
        ];
    }
}
