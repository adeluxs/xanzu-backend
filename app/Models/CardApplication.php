<?php

namespace App\Models;

use App\Enums\CardApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardApplication extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'status' => CardApplicationStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
