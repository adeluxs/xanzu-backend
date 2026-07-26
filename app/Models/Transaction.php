<?php

namespace App\Models;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Transaction extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = ['day'];

    protected $searchable = [
        'amount',
        'tnx',
        'type',
        'method',
        'description',
        'status',
        'created_at',
    ];

    /*
     * Scope Declaration
     * */

    protected function scopeStatus($query, $status)
    {
        if ($status && $status != 'all') {
            return $query->where('status', $status);
        }

        return $query;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function (Transaction $model) {
            $model->tnx = $model->tnx ?? 'TNX'.strtoupper(Str::random(10));
        });
    }

    protected function scopeProfit($query)
    {
        return $query->whereIn('type', [TxnType::Referral, TxnType::SignupBonus, TxnType::AdsViewed]);
    }

    protected function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($query) use ($search) {
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('username', 'like', '%'.$search.'%');
                })->orWhere('tnx', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    protected function scopeType($query, $type)
    {

        if ($type == 'withdraw_all' || $type == 'payout_all') {
            return $query->whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto]);
        } elseif ($type == 'deposit_all') {
            return $query->whereIn('type', [TxnType::Deposit, TxnType::ManualDeposit]);
        } elseif ($type == 'referral_all') {
            return $query->whereIn('type', [TxnType::Referral, TxnType::SignupBonus]);
        } elseif ($type == 'refund_all') {
            return $query->whereIn('type', [TxnType::Refund, TxnType::OrderRefunded]);
        } elseif ($type == 'order_all') {
            return $query->whereIn('type', [TxnType::ProductOrder, TxnType::ProductOrderViaTopup]);
        } elseif ($type == 'seller_all') {
            return $query->whereIn('type', [TxnType::ProductSold]);
        } elseif ($type == 'deduction_all') {
            return $query->whereIn('type', [TxnType::Subtract, TxnType::BnplInstallment]);
        } elseif ($type == 'all') {
            return $query;
        }

        return $query->where('type', $type);

    }

    protected function scopePending($query)
    {
        return $query->where('status', TxnStatus::Pending->value);
    }

    protected function scopeRejected($query)
    {
        return $query->where('status', TxnStatus::Failed->value);
    }

    protected function scopeOwn($query, $user = null)
    {
        return $query->where('user_id', $user?->id ?? auth()->id());
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(get: function () {
            return Date::parse($this->attributes['created_at'])->format('d M Y h:i A');
        });
    }

    protected function day(): Attribute
    {
        return Attribute::make(get: function () {
            return Date::parse($this->attributes['created_at'])->format('d M');
        });
    }

    protected function scopeTnx($query, $tnx)
    {
        return $query->where('tnx', $tnx)->first();
    }

    public function referral()
    {
        return $this->referrals()->where('type', '=', $this->target_type);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referral_target_id', 'target_id')->where('type', '=', $this->target_type);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function level()
    {
        return $this->belongsTo(LevelReferral::class, 'target_id', 'the_order');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function totalDeposit()
    {
        return $this->where('status', TxnStatus::Success)->whereIn('type', [TxnType::Deposit, TxnType::ManualDeposit]);
    }

    public function totalWithdraw()
    {
        return $this->where('status', TxnStatus::Success)->where(function ($query) {
            $query->where('type', TxnType::Withdraw)
                ->orWhere('type', TxnType::WithdrawAuto);
        });
    }

    public function totalProfit()
    {
        return $this->where('status', TxnStatus::Success)->where(function ($query) {
            $query->whereIn('type', [TxnType::Referral, TxnType::SignupBonus]);
        });
    }

    public function totalDepositBonus()
    {
        return $this->where('status', TxnStatus::Success)->where(function ($query) {
            $query->where('target_id', '!=', null)
                ->where('target_type', 'deposit')
                ->where('type', TxnType::Referral);
        })->sum('amount');
    }

    protected function method(): Attribute
    {
        return new Attribute(
            get: fn ($value) => ucwords($value),
        );
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    protected function casts(): array
    {
        return [
            'type' => TxnType::class,
            'status' => TxnStatus::class,
            'pay_amount' => 'double',
            'amount' => 'double',
        ];
    }

    /**
     * Scope a query to only include pending manual txn
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePendingManualTxn($query)
    {
        return $query->where('status', TxnStatus::Pending)
            ->whereIn('type', [TxnType::Deposit, TxnType::ManualDeposit, TxnType::BnplInstallment, TxnType::ProductOrder]);
    }
}
