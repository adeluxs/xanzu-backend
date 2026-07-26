<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait ModelIdEncDec
{
    protected function getEncIdAttribute()
    {
        return Crypt::encrypt($this->id);
    }

    protected function getDecIdAttribute()
    {
        return Crypt::decrypt($this->id);
    }
}
