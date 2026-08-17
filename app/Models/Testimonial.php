<?php

namespace App\Models;

use App\Support\LandingCache;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(static fn () => LandingCache::flush());
        static::deleted(static fn () => LandingCache::flush());
    }

    public function trans()
    {
        return $this->hasMany(Testimonial::class, 'locale_id');
    }

    /**
     * Get the translated
     *
     * @param  string  $value
     * @return string
     */
    protected function translated(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return $this->trans()->where('locale', app()->getLocale())->first() ?? $this;
        });
    }

    protected function casts(): array
    {
        return [
            'locale' => 'string',
        ];
    }
}
