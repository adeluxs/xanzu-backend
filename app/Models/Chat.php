<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chats';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'seen',
    ];

    protected $appends = ['chatTime'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function attachments()
    {
        return $this->hasMany(ChatAttachments::class, 'chat_id');
    }

    /**
     * Get the role
     *
     * @param  string  $value
     * @return string
     */
    protected function role(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return $this->sender_id == auth()->id() ? 'sender' : 'receiver';
        });
    }

    /**
     * Scope a query to only include lastChat
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeLastChat($query)
    {
        return $query->where('sender_id', auth()->id())->orWhere('receiver_id', auth()->id());
    }

    /**
     * Scope a query to only include myChatWith
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeMyChatWith($query, $userId, $authUser = null)
    {
        $authUser = $authUser ?? auth('web')->id();

        return $query->where(function ($query) use ($authUser, $userId) {
            $query->where('sender_id', $authUser)->where('receiver_id', $userId);
        })
            ->orWhere(function ($query) use ($authUser, $userId) {
                $query->where('sender_id', $userId)->where('receiver_id', $authUser);
            });
    }

    /**
     * Get the chatTime
     *
     * @param  string  $value
     * @return string
     */
    protected function chatTime(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return $this->created_at?->format('h:i A');
        });
    }
}
