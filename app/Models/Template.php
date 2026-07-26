<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts()
    {
        return [
            'notification_status' => 'boolean',
            'email_status' => 'boolean',
            'sms_status' => 'boolean',
            'footer_status' => 'boolean',
        ];
    }

    public function scopeOrder($query, string $order)
    {
        return $query->orderBy('id', $order);
    }

    public function scopeSearch($query, $search)
    {
        if ($search !== null) {
            return $query->whereAny([
                'name',
                'code',
                'for',
            ], 'like', '%'.$search.'%');
        }

        return $query;
    }
}
