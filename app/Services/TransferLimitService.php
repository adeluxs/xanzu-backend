<?php

namespace App\Services;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Models\Transaction;
use App\Models\TransferLimit;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TransferLimitService
{
    public function enforce(User $sender, float $amount): void
    {
        $summary = $this->summary($sender);
        $limit = $summary['limit_model'];

        if (! $limit) {
            return;
        }

        if ($amount < (float) $limit->min_amount) {
            throw ValidationException::withMessages([
                'amount' => __('Minimum transfer amount is :min.', ['min' => $limit->min_amount]),
            ]);
        }

        if ((float) $limit->max_amount > 0 && $amount > (float) $limit->max_amount) {
            throw ValidationException::withMessages([
                'amount' => __('Maximum transfer amount is :max.', ['max' => $limit->max_amount]),
            ]);
        }

        if ((float) $limit->daily_limit > 0 && ($summary['daily_sent'] + $amount) > (float) $limit->daily_limit) {
            throw ValidationException::withMessages([
                'amount' => __('Daily transfer limit of :limit exceeded. Remaining: :remaining.', [
                    'limit' => $limit->daily_limit,
                    'remaining' => $summary['daily_remaining'],
                ]),
            ]);
        }

        if ((int) $limit->daily_transaction_count > 0 && $summary['daily_count'] >= (int) $limit->daily_transaction_count) {
            throw ValidationException::withMessages([
                'transfer' => __('Daily transfer limit of :count transactions reached.', [
                    'count' => $limit->daily_transaction_count,
                ]),
            ]);
        }

        if ((float) $limit->monthly_limit > 0 && ($summary['monthly_sent'] + $amount) > (float) $limit->monthly_limit) {
            throw ValidationException::withMessages([
                'amount' => __('Monthly transfer limit of :limit exceeded. Remaining: :remaining.', [
                    'limit' => $limit->monthly_limit,
                    'remaining' => $summary['monthly_remaining'],
                ]),
            ]);
        }

        if ((int) $limit->monthly_transaction_count > 0 && $summary['monthly_count'] >= (int) $limit->monthly_transaction_count) {
            throw ValidationException::withMessages([
                'transfer' => __('Monthly transfer limit of :count transactions reached.', [
                    'count' => $limit->monthly_transaction_count,
                ]),
            ]);
        }
    }

    /**
     * Returns the active transfer policy plus current usage. Only successful
     * outbound transfer rows are counted, so failed/pending transactions and
     * the recipient-side mirror transaction cannot consume a sender's limit.
     */
    public function summary(User $sender): array
    {
        $limit = TransferLimit::getLimitFor((string) $sender->user_type);

        $baseQuery = Transaction::query()
            ->where('user_id', $sender->id)
            ->where('type', TxnType::Transfer->value)
            ->where('status', TxnStatus::Success->value)
            // Sender-side transfer rows are written with from_user_id = recipient.
            // Recipient mirror rows are written with from_user_id = sender. A user
            // therefore only counts rows whose description begins with Transfer to.
            ->where('description', 'like', 'Transfer to %');

        $dayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        // One aggregate query replaces four separate SUM/COUNT queries. This
        // endpoint is hit when the mobile Send Money screen opens and again at
        // validation time, so keeping it to one indexed monthly scan matters.
        $usage = $baseQuery
            ->where('created_at', '>=', $monthStart)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END), 0) AS daily_sent, '.
                'COALESCE(SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END), 0) AS daily_count, '.
                'COALESCE(SUM(amount), 0) AS monthly_sent, COUNT(*) AS monthly_count',
                [$dayStart, $dayStart]
            )
            ->first();

        $dailySent = (float) ($usage?->daily_sent ?? 0);
        $dailyCount = (int) ($usage?->daily_count ?? 0);
        $monthlySent = (float) ($usage?->monthly_sent ?? 0);
        $monthlyCount = (int) ($usage?->monthly_count ?? 0);

        $dailyLimit = $limit ? (float) $limit->daily_limit : 0.0;
        $monthlyLimit = $limit ? (float) $limit->monthly_limit : 0.0;
        $dailyCountLimit = $limit ? (int) $limit->daily_transaction_count : 0;
        $monthlyCountLimit = $limit ? (int) $limit->monthly_transaction_count : 0;

        return [
            'limit_model' => $limit,
            'min_amount' => $limit ? (float) $limit->min_amount : 0.0,
            'max_amount' => $limit ? (float) $limit->max_amount : 0.0,
            'daily_limit' => $dailyLimit,
            'daily_sent' => $dailySent,
            'daily_remaining' => $dailyLimit > 0 ? max(0.0, $dailyLimit - $dailySent) : null,
            'daily_transaction_count' => $dailyCountLimit,
            'daily_count' => $dailyCount,
            'daily_transactions_remaining' => $dailyCountLimit > 0 ? max(0, $dailyCountLimit - $dailyCount) : null,
            'monthly_limit' => $monthlyLimit,
            'monthly_sent' => $monthlySent,
            'monthly_remaining' => $monthlyLimit > 0 ? max(0.0, $monthlyLimit - $monthlySent) : null,
            'monthly_transaction_count' => $monthlyCountLimit,
            'monthly_count' => $monthlyCount,
            'monthly_transactions_remaining' => $monthlyCountLimit > 0 ? max(0, $monthlyCountLimit - $monthlyCount) : null,
            'has_limit' => $limit !== null,
        ];
    }
}
