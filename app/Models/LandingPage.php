<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Scope a query to only include theme
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeTheme($query)
    {
        return $query->where('theme', site_theme());
    }
}
