<?php

namespace App\Services;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawAccount;
use App\Models\WithdrawalSchedule;
use App\Services\Payments\RayplusmoneyService;
use App\Traits\NotifyTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WithdrawService
{
    use NotifyTrait;

    public function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'withdraw_account' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Create a withdrawal safely. Automatic RayPlusMoney withdrawals are
     * dispatched immediately after the wallet debit/transaction commit.
     */
    public function withdrawMoney(int|string $withdrawAccountId, float $amount): array
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if (! $authUser) {
            throw ValidationException::withMessages(['auth' => __('Unauthenticated.')]);
        }

        if (WithdrawalSchedule::query()->where('status', false)->where('name', Carbon::now()->format('l'))->exists()) {
            throw ValidationException::withMessages(['withdraw' => __('Today is the off day of withdraw')]);
        }

        if (! (bool) setting($authUser->user_type.'_withdraw', 'permission', true) || ! (bool) $authUser->withdraw_status) {
            throw ValidationException::withMessages(['withdraw' => __('Withdraw currently unavailable')]);
        }

        $account = WithdrawAccount::query()
            ->with(['method.gateway'])
            ->where('user_id', $authUser->id)
            ->find($withdrawAccountId);

        if (! $account) {
            throw ValidationException::withMessages(['withdraw_account' => __('Withdraw account not found or does not belong to you.')]);
        }

        $method = $account->method;
        if (! $method || ! $method->id || ! (bool) $method->status) {
            throw ValidationException::withMessages(['withdraw_account' => __('This withdraw method is unavailable.')]);
        }

        $amount = round((float) $amount, 2);
        $min = max(0, (float) $method->min_withdraw);
        $max = max(0, (float) $method->max_withdraw);
        if ($amount < $min || ($max > 0 && $amount > $max)) {
            $message = $max > 0
                ? __('Please ensure the withdrawal amount is between :min to :max', [
                    'min' => amountWithCurrency($min),
                    'max' => amountWithCurrency($max),
                ])
                : __('The minimum withdrawal amount is :min', ['min' => amountWithCurrency($min)]);
            throw ValidationException::withMessages(['amount' => $message]);
        }

        $dailyLimit = max(0, (int) setting('withdraw_day_limit', 'fee', 0));
        if ($dailyLimit > 0) {
            $todayCount = Transaction::query()
                ->where('user_id', $authUser->id)
                ->whereIn('type', [TxnType::Withdraw->value, TxnType::WithdrawAuto->value])
                ->whereNotIn('status', [TxnStatus::Failed->value, TxnStatus::Cancelled->value])
                ->whereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])
                ->count();

            if ($todayCount >= $dailyLimit) {
                throw ValidationException::withMessages(['amount' => __('Today Withdraw limit has been reached')]);
            }
        }

        $rate = (float) $method->rate;
        if (! is_finite($rate) || $rate <= 0) {
            throw ValidationException::withMessages(['withdraw_account' => __('The selected withdraw method has an invalid conversion rate.')]);
        }

        $rawCharge = max(0, (float) $method->charge);
        $charge = $method->charge_type === 'percentage' ? (($rawCharge / 100) * $amount) : $rawCharge;
        $charge = round($charge, 2);
        $totalAmount = round($amount + $charge, 2);
        $payAmount = round($amount * $rate, 8);
        $type = $method->type === 'auto' ? TxnType::WithdrawAuto : TxnType::Withdraw;

        $transaction = DB::transaction(function () use ($authUser, $account, $method, $amount, $charge, $totalAmount, $payAmount, $type) {
            /** @var User $user */
            $user = User::query()->whereKey($authUser->id)->lockForUpdate()->firstOrFail();

            if (! (bool) $user->withdraw_status) {
                throw ValidationException::withMessages(['withdraw' => __('Withdraw currently unavailable')]);
            }
            if ((float) $user->balance < $totalAmount) {
                throw ValidationException::withMessages(['amount' => __('Insufficient Balance')]);
            }

            $user->balance = round((float) $user->balance - $totalAmount, 2);
            $user->save();

            return Transaction::query()->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'charge' => $charge,
                'final_amount' => $totalAmount,
                'description' => 'Withdraw With '.$account->method_name,
                'type' => $type,
                'status' => TxnStatus::Pending,
                'pay_amount' => $payAmount,
                'pay_currency' => strtoupper((string) $method->currency),
                'method' => $method->name,
                'manual_field_data' => $account->credentials,
            ]);
        }, 3);

        $gatewayResult = null;
        if ($method->type === 'auto') {
            $gatewayCode = strtolower((string) ($method->gateway?->gateway_code ?? ''));
            if ($gatewayCode === RayplusmoneyService::GATEWAY_CODE) {
                try {
                    $gatewayResult = app(RayplusmoneyService::class)->createPayout($transaction);
                } catch (\Throwable $e) {
                    Log::warning('Automatic RayPlusMoney withdrawal dispatch failed.', [
                        'tnx' => $transaction->tnx,
                        'user_id' => $authUser->id,
                        'error' => $e->getMessage(),
                    ]);
                    // RayplusmoneyService refunds and marks the transaction failed
                    // when provider dispatch is rejected. Surface the real reason.
                    throw ValidationException::withMessages(['withdraw' => $e->getMessage()]);
                }
            }
        } else {
            $this->notifyAdminSafely($transaction, $authUser);
        }

        $fresh = $transaction->fresh();

        return [
            'transaction' => $fresh,
            'gateway' => $gatewayResult,
            'status' => $fresh->status instanceof TxnStatus ? $fresh->status->value : (string) $fresh->status,
        ];
    }

    private function notifyAdminSafely(Transaction $transaction, User $user): void
    {
        try {
            $shortcodes = [
                '[[amount]]' => amountWithCurrency($transaction->amount),
                '[[charge]]' => amountWithCurrency($transaction->charge),
                '[[gateway]]' => $transaction->method,
                '[[request_at]]' => $transaction->created_at,
                '[[total_amount]]' => amountWithCurrency($transaction->final_amount),
                '[[request_link]]' => route('admin.withdraw.pending'),
                '[[site_title]]' => setting('site_title', 'global'),
                '[[currency]]' => setting('site_currency', 'global'),
            ];

            $this->sendNotify(
                $user->email,
                'admin_withdraw_request',
                'Admin',
                $shortcodes,
                $user->phone,
                $user->id,
                route('admin.withdraw.pending')
            );
        } catch (\Throwable $e) {
            // Notification delivery must never roll back a committed wallet transaction.
            Log::warning('Withdrawal created but admin notification failed.', [
                'tnx' => $transaction->tnx,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
