<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'status',
        'description',
        'is_popular',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Brand $brand) {
            $brand->slug = Str::slug($brand->name);

            if (self::where('slug', $brand->slug)->exists()) {
                $brand->slug = $brand->slug . '-' . (self::max('id') ?? 0);
            }
        });
    }

    public function products()
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * Scope a query to only include is_popular
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIsPopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope a query to only include active
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
