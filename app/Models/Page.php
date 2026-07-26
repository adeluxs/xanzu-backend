<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('title', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%');
        }

        return $query;
    }

    /**
     * Scope a query to only include theme
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeTheme($query, $theme = null)
    {
        return $query->where('theme', $theme ?? site_theme());
    }

    protected function scopeLocale($query, $locale = null)
    {
        return $query->where('locale', $locale ?? app()->getLocale());
    }

}
