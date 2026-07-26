<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Str;

class Blog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function createdAt(): Attribute
    {
        return Attribute::make(get: function () {
            return Date::parse($this->attributes['created_at'])->format('d F Y');
        });
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function (Blog $blog) {
            $blog->slug = Str::slug($blog->title);

            if (self::where('slug', $blog->slug)->exists()) {
                $blog->slug = $blog->slug.'-'.self::max('id');
            }
        });
    }

    protected function unModifyCreatedAt(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->attributes['created_at'];
        });
    }

    protected function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('details', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    public function parent()
    {
        return $this->belongsTo(Blog::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Blog::class, 'parent_id');
    }
}
