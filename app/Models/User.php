<?php

namespace App\Models;

use App\Enums\KYCStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Services\CardBalanceSyncService;
use App\Services\CardStatusSyncService;
use Coderflex\LaravelTicket\Concerns\HasTickets;
use Coderflex\LaravelTicket\Contracts\CanUseTickets;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements CanUseTickets, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasTickets, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'avatar',
        'first_name',
        'last_name',
        'country',
        'phone',
        'username',
        'email',
        'email_verified_at',
        'gender',
        'date_of_birth',
        'city',
        'state',
        'zip_code',
        'address',
        'balance',
        'status',
        'google2fa_secret',
        'two_fa',
        'deposit_status',
        'withdraw_status',
        'transfer_status',
        'otp_status',
        'referral_status',
        'ref_id',
        'password',
        'phone_verified',
        'otp',
        'close_reason',
        'show_following_follower_list',
        'accept_profile_chat',
        'balance',
        'kyc',
        'current_plan_id',
        'plan_id',
        'about',
        'is_popular',
        'user_type',
        'total_reviews',
        'avg_rating',
        'phone_verified_at',
        'current_credit_limit_id',
        'credit_limit_amount',
        'used_credit_limit_amount',
        'remaining_credit_limit_amount',
        'card_status',
        'default_split', // for outside purchase split
        'api_key',
        'signature',
        'public_key',
        'secret_key',
        'webhook_secret',
    ];

    protected $appends = [
        'full_name',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    /*
     * Scope Declaration
     * */

    protected function scopeSearch($query, $search)
    {
        if ($search != null) {
            return $query->where(function ($query) use ($search) {
                $query->orWhere('first_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('username', 'LIKE', '%' . $search . '%')
                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $search . '%');
            });
        }

        return $query;
    }

    protected function avatarPath(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->avatar != null && file_exists(base_path('assets/' . $this->avatar)) ? asset($this->avatar) : self::getDefaultAvatar();
        });
    }

    protected static function getDefaultAvatar()
    {
        return asset('frontend/' . site_theme() . '/images/user/user-default.png');
    }

    protected function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    protected function scopeClosed($query)
    {
        return $query->where('status', 2);
    }

    protected function scopeDisabled($query)
    {
        return $query->where('status', 0);
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(get: function () {
            return Date::parse($this->attributes['updated_at'])->format('d M Y h:i A');
        });
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(get: function () {
            return Date::parse($this->attributes['created_at'])->format('d M Y h:i A');
        });
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(get: function () {
            return ucwords($this->first_name . ' ' . $this->last_name);
        });
    }

    protected function getTotalProfitAttribute(): string
    {
        return $this->totalProfit();
    }

    protected function getTotalDepositAttribute(): string
    {
        return $this->totalDeposit();
    }

    public function totalProfit($days = null)
    {
        $sum = $this->transaction()->where('status', TxnStatus::Success)->whereIn('type', [TxnType::Referral, TxnType::ProductSold, TxnType::SignupBonus]);

        if ($days != null) {
            $sum->where('created_at', '>=', Date::now()->subDays((int) $days));
        }

        $sum = $sum->sum('amount');

        return round($sum, 2);
    }

    /**
     * Get the isSeller
     *
     *
     * @param  string  $value
     * @return string
     */
    protected function isSeller(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->user_type == 'merchant';
        });
    }

    public function kycs()
    {
        return $this->hasMany(UserKyc::class, 'user_id', 'id');
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function creditLimit()
    {
        return $this->belongsTo(CreditLimit::class, 'current_credit_limit_id');
    }

    public function cardApplications()
    {
        return $this->hasMany(CardApplication::class);
    }

    public function cardHolder()
    {
        return $this->hasOne(CardHolder::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function getReferrals()
    {
        return ReferralProgram::all()->map(function ($program) {
            return ReferralLink::getReferral($this, $program);
        });
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'ref_id');
    }

    public function totalDeposit()
    {
        $sum = $this->transaction()->where('status', TxnStatus::Success)->where(function ($query) {
            $query->where('type', TxnType::Deposit)
                ->orWhere('type', TxnType::ManualDeposit);
        })->sum('amount');

        return round($sum, 2);
    }

    public function totalDepositBonus()
    {
        $sum = $this->transaction()->where('status', TxnStatus::Success)->where(function ($query) {
            $query->where('target_id', '!=', null)
                ->where('target_type', 'deposit')
                ->where('type', TxnType::Referral);
        })->sum('amount');

        return round($sum, 2);
    }

    public function totalWithdraw()
    {
        $sum = $this->transaction()->where('status', TxnStatus::Success)->whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto])->sum('amount');

        return round($sum, 2);
    }

    public function totalReferralProfit()
    {
        $sum = $this->transaction()->where('status', TxnStatus::Success)->where(function ($query) {
            $query->where('type', TxnType::Referral);
        })->sum('amount');

        return round($sum, 2);
    }

    public function ticket()
    {
        return $this->hasMany(Ticket::class);
    }

    protected function google2faSecret(): Attribute
    {
        return new Attribute(
            get: fn($value) => $value != null ? Crypt::decryptString($value) : $value,
            set: fn($value) => Crypt::encryptString($value),
        );
    }

    public function activities()
    {
        return $this->hasMany(LoginActivities::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function refferelLinks()
    {
        return $this->hasMany(ReferralLink::class);
    }

    public function withdrawAccounts()
    {
        return $this->hasMany(WithdrawAccount::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'seller_id');
    }

    public function provider()
    {
        return $this->hasOne(Provider::class);
    }

    public function recentSearches()
    {
        return $this->hasMany(RecentSearch::class);
    }

    public function shippingAddresses()
    {
        return $this->hasMany(ShippingAddress::class);
    }

    /**
     * Get the totalSuccessRate
     *
     * @param  string  $value
     * @return string
     */
    protected function orderSuccessRate(): Attribute
    {
        return Attribute::make(get: function () {
            $totalSold = $this->transaction()->where('type', TxnType::ProductSold)->count();
            $totalSuccess = $this->transaction()->where('type', TxnType::ProductSold)->where('status', TxnStatus::Success)->count();

            return $totalSold > 0 ? round(($totalSuccess / $totalSold) * 100, 2) : 0;
        });
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'follow_to', 'follow_from');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follow_from', 'follow_to');
    }

    /**
     * Scope a query to only include popular
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Get the flag
     *
     * @param  string  $value
     * @return string
     */
    protected function flag(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return collect(getCountries())->where('name', $this->country ?? 'Bangladesh')->first();
        });
    }

    /**
     * Get the avatar text
     *
     * @param  string  $value
     * @return string
     */
    protected function avatarText(): Attribute
    {
        return Attribute::make(get: function ($value) {
            $text = '';
            if (isset($this->first_name) && isset($this->last_name)) {
                $text = strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
            } elseif (isset($this->first_name)) {
                $text .= strtoupper(substr($this->first_name, 0, 1));
            } elseif (isset($this->last_name)) {
                $text .= strtoupper(substr($this->last_name, 0, 1));
            }

            return $text ?: '?';
        });
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_fa' => 'boolean',
            'phone_verified' => 'boolean',
            'notifications_permission' => 'array',
            'validity_at' => 'datetime',
            'card_status' => 'boolean',
            'deposit_status' => 'boolean',
            'withdraw_status' => 'boolean',
            'transfer_status' => 'boolean',
            'transfer_kyc_verified' => 'boolean',
        ];
    }

    public function buyerReviews()
    {
        return $this->hasMany(ListingReview::class, 'buyer_id')->approved();
    }

    public function sellerReviews()
    {
        return $this->hasMany(ListingReview::class, 'seller_id')->whereNull('parent_id')->approved();
    }

    public function isPhoneVerified()
    {
        return !is_null($this->phone_verified);
    }

    /**
     * Scope a query to only include status
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeStatus($query, $status)
    {
        if ($status != 'all' && $status != null) {
            $status = $status == 'pending' ? KYCStatus::Pending : ($status === 'rejected' ? KYCStatus::Failed : KYCStatus::Verified);

            return $query->where('kyc', $status);
        } else {
            return $query;
        }
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            $user->referral_code = Str::random(setting('referral_code_limit', 'global'));
        });

        static::updated(function ($user) {
            if ($user->wasChanged('remaining_credit_limit_amount')) {
                app(CardBalanceSyncService::class)->syncUserCardBalance($user);
            }

            if ($user->wasChanged('card_status')) {
                app(CardStatusSyncService::class)->syncUserCardStatus($user);
            }
        });
    }

    public function isBnplEligible()
    {
        if (!setting('buyer_purchase', 'permission')) {
            return false;
        }

        if (setting('bnpl_for_kyc_verified_only', 'permission') && (($this->kyc?->value ?? $this->kyc) != KYCStatus::Verified->value)) {
            return false;
        }
        return $this->remaining_credit_limit_amount > 0 && $this->card_status == true;

    }
}
