<?php

namespace App\Models;

use App\Enums\NavigationType;
use App\Support\JsonData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Navigation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function page()
    {
        return $this->belongsTo(Page::class)->withDefault();
    }

    protected function tname(): Attribute
    {
        return Attribute::make(get: function () {
            $jsonData = JsonData::decodeArray($this->translate);

            return $jsonData[session()->get('locale') ?? config('app.locale')] ?? $this->name;
        });
    }

    /**
     * Scope a query to only include footer
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeFooter($query)
    {
        return $query->where('type', 'like', '%footer-%');
    }

    protected function scopeHeader($query)
    {
        return $query->where('type', 'like', '%'.NavigationType::Header->value.'%');
    }

    /**
     * The attributes that should be cast to native types.
     */
    protected function casts(): array
    {
        return [
            'type' => 'json',
        ];
    }
}
