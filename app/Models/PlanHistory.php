<?php

namespace App\Models;

use App\Enums\PlanHistoryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanHistory extends Model
{
    use HasFactory;

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'validity_at' => 'datetime',
            'status' => PlanHistoryStatus::class,
            'charge_value' => 'float',
        ];
    }
}
