<?php

namespace App\Models;

use App\Support\LandingCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingContent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(static fn () => LandingCache::flush());
        static::deleted(static fn () => LandingCache::flush());
    }
}
