<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CourierPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'admin_note',
        'short_description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (CourierPartner $courierPartner) {
            $courierPartner->slug = Str::slug($courierPartner->name);

            if (self::where('slug', $courierPartner->slug)->exists()) {
                $courierPartner->slug = $courierPartner->slug . '-' . (self::max('id') ?? 0);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
