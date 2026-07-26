<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_title',
        'description',
        'image',
        'order',
        'status',
        'slug',
        'is_trending',
        'in_landing_hero',
        'parent_id',
    ];

    /**
     * Scope a query to only include active
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'category_id');
    }

    public function subCatListings()
    {
        return $this->hasMany(Listing::class, 'subcategory_id');
    }

    public function subCategoryPublicListings()
    {
        return $this->hasMany(Listing::class, 'subcategory_id')->public();
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function childrens()
    {
        return $this->children();
    }

    /**
     * Scope a query to only include isCategory
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeIsCategory($query)
    {
        return $query->where('parent_id', null);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function (Category $category) {
            $category->slug = Str::slug($category->name);

            if (self::where('slug', $category->slug)->exists()) {
                $category->slug = $category->slug.'-'.self::max('id');
            }
        });
    }

    /**
     * Scope a query to only include trending
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeTrending($query)
    {
        return $query->where('is_trending', 1);
    }

    protected function scopeIsLandingHero($query)
    {
        return $query->where('in_landing_hero', 1);
    }

    public function url(): Attribute
    {
        return new Attribute(
            get: fn () => $this->parent_id ? route('sub.category.listing', [$this->parent->slug, $this->slug]) : route('category.listing', ['category' => $this->slug]),
        );
    }
}
