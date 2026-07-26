<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserKyc extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['type'];

    public function kyc()
    {
        return $this->hasOne(Kyc::class, 'id', 'kyc_id');
    }

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function type(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->relationLoaded('kyc') ? optional($this->getRelation('kyc'))->name : null,
        );
    }
}
