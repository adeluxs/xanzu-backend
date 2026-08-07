<?php

namespace App\Services;

use App\Models\TransferLimit;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferLimitService
{
    public function enforce(User $sender, float $amount): void
    {
        $limit = TransferLimit::getLimitFor($sender->user_type);

        if (!$limit) {
            return;
        }

        if ($amount < $limit->min_amount) {
            throw ValidationException::withMessages([
                'amount' => __('Minimum transfer amount is :min.', ['min' => $limit->min_amount]),
            ]);
        }

        if ($limit->max_amount > 0 && $amount > $limit->max_amount) {
            throw ValidationException::withMessages([
                'amount' => __('Maximum transfer amount is :max.', ['max' => $limit->max_amount]),
            ]);
        }

        $this->enforceDailyLimits($sender, $amount, $limit);
        $this->enforceMonthlyLimits($sender, $amount, $limit);
    }

    private function enforceDailyLimits(User $sender, float $amount, TransferLimit $limit): void
    {
        $today = now()->startOfDay();

        $dailySent = Transaction::where('user_id', $sender->id)
            ->where('type', 'transfer')
            ->where('created_at', '>=', $today)
            ->sum('amount');

        if ($limit->daily_limit > 0 && ($dailySent + $amount) > $limit->daily_limit) {
            $remaining = max(0, $limit->daily_limit - $dailySent);
            throw ValidationException::withMessages([
                'amount' => __('Daily transfer limit of :limit exceeded. Remaining: :remaining.', [
                    'limit' => $limit->daily_limit,
                    'remaining' => $remaining,
                ]),
            ]);
        }

        if ($limit->daily_transaction_count > 0) {
            $dailyCount = Transaction::where('user_id', $sender->id)
                ->where('type', 'transfer')
                ->where('created_at', '>=', $today)
                ->count();

            if ($dailyCount >= $limit->daily_transaction_count) {
                throw ValidationException::withMessages([
                    'transfer' => __('Daily transfer limit of :count transactions reached.', [
                        'count' => $limit->daily_transaction_count,
                    ]),
                ]);
            }
        }
    }

    private function enforceMonthlyLimits(User $sender, float $amount, TransferLimit $limit): void
    {
        $monthStart = now()->startOfMonth();

        $monthlySent = Transaction::where('user_id', $sender->id)
            ->where('type', 'transfer')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        if ($limit->monthly_limit > 0 && ($monthlySent + $amount) > $limit->monthly_limit) {
            throw ValidationException::withMessages([
                'amount' => __('Monthly transfer limit of :limit exceeded.', ['limit' => $limit->monthly_limit]),
            ]);
        }

        if ($limit->monthly_transaction_count > 0) {
            $monthlyCount = Transaction::where('user_id', $sender->id)
                ->where('type', 'transfer')
                ->where('created_at', '>=', $monthStart)
                ->count();

            if ($monthlyCount >= $limit->monthly_transaction_count) {
                throw ValidationException::withMessages([
                    'transfer' => __('Monthly transfer limit of :count transactions reached.', [
                        'count' => $limit->monthly_transaction_count,
                    ]),
                ]);
            }
        }
    }
}
