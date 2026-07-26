<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class CronJobLog extends Model
{
    use HasFactory;

    protected function duration(): Attribute
    {
        return Attribute::make(get: function () {
            return Date::parse($this->started_at)->diffInSeconds($this->ended_at);
        });
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
