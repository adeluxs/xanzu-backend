<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardHolder extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeCurrentUser($query, $user_id = null)
    {
        return $query->where('user_id', $user_id ?? auth()->id());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
