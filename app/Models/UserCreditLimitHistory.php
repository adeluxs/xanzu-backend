<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCreditLimitHistory extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creditLimit()
    {
        return $this->belongsTo(CreditLimit::class);
    }
}
