<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLimit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_kyc' => 'boolean',
            'status' => 'boolean',
        ];
    }

    protected function scopeActive($query)
    {
        $query->where('status', 1);
    }

    public function splits()
    {
        return $this->hasMany(CreditLimitSplit::class);
    }
}
