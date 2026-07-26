<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = ['avatar', 'name', 'email', 'phone', 'password', 'device_token', 'is_admin', 'status'];

    protected function createdAt(): Attribute
    {
        return Attribute::make(get: function () {
            return Date::parse($this->attributes['created_at'])->format('M d Y h:i');
        });
    }
}
