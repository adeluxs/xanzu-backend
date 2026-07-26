<?php

namespace App\Services;

use App\Models\User;

class CreditLimitService
{
    public function adjustUsed(User $user, float $usedDelta): void
    {
        $creditLimit = max(0, round((float) $user->credit_limit_amount, 2));
        $nextUsed = round((float) $user->used_credit_limit_amount + $usedDelta, 2);
        $nextUsed = min($creditLimit, max(0, $nextUsed));
        $nextRemaining = max(0, round($creditLimit - $nextUsed, 2));

        $user->update([
            'credit_limit_amount' => $creditLimit,
            'used_credit_limit_amount' => $nextUsed,
            'remaining_credit_limit_amount' => $nextRemaining,
        ]);
    }
}
