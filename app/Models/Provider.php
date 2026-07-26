<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'cover_image',
        'website_url',
        'platform',
        'platform_host',
        'api_key',
        'api_secret',
        'description',
        'status',
        'user_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Provider $provider) {
            $provider->slug = Str::slug($provider->name);

            if (self::where('slug', $provider->slug)->exists()) {
                $provider->slug = $provider->slug . '-' . (self::max('id') ?? 0);
            }
        });
    }

    /**
     * Get all listings for this provider.
     */
    public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
