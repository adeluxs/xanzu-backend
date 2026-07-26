<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

class Message extends \Coderflex\LaravelTicket\Models\Message
{
    use HasFactory;
    use SoftDeletes;

    public function user(): BelongsTo
    {
        $model = $this->model == 'admin' ? Admin::class : User::class;

        return $this->belongsTo($model)->withDefault();
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    public function casts()
    {
        return [
            'attachments' => 'array',
        ];
    }
}
