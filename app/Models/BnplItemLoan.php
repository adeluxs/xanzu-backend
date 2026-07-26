<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BnplItemLoan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_item_amount' => 'decimal:2',
            'initial_paid_amount' => 'decimal:2',
            'final_amount_to_pay' => 'decimal:2',
            'remaining_due_amount' => 'decimal:2',
            'interest_rate_amount' => 'decimal:2',
            'delay_fine_amount' => 'decimal:2',
        ];
    }


    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function split()
    {
        return $this->belongsTo(CreditLimitSplit::class, 'credit_limit_split_id');
    }

    public function installments()
    {
        return $this->hasMany(BnplInstallment::class)->orderBy('installment_no');
    }
}
