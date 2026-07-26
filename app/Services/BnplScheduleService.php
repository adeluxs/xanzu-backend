<?php

namespace App\Services;

use App\Models\CreditLimitSplit;
use Carbon\Carbon;

class BnplScheduleService
{
    public function calculateInterest(float $principal, float $rateAmount, string $rateType): float
    {
        if ($rateType === 'percentage') {
            return round(($principal * $rateAmount) / 100, 2);
        }

        return round($rateAmount, 2);
    }

    public function calculateDueDate(int $step, int $intervalAmount, string $intervalType, ?Carbon $baseDate = null): Carbon
    {
        $intervalAmount = max(1, $intervalAmount);
        $totalInterval = $step * $intervalAmount;
        $baseDate = $baseDate ? $baseDate->copy() : now();

        return match ($intervalType) {
            'day' => $baseDate->addDays($totalInterval),
            'week' => $baseDate->addWeeks($totalInterval),
            default => $baseDate->addMonths($totalInterval),
        };
    }

    public function buildSchedulePreview(CreditLimitSplit $split, float $amount, bool $takeInitialInstallment, ?Carbon $baseDate = null): array
    {
        $baseDate = $baseDate ? $baseDate->copy() : now();
        $splitCount = max(1, (int) round((float) ($split->total_split ?: 1)));
        $orderAmount = round($amount, 2);

        $upfrontAmount = 0.0;
        if ($takeInitialInstallment) {
            $upfrontAmount = round($orderAmount / $splitCount, 2);
        }

        $financedAmount = round(max($orderAmount - $upfrontAmount, 0), 2);
        $remainingInstallments = $splitCount - ($upfrontAmount > 0 ? 1 : 0);
        $startNo = $upfrontAmount > 0 ? 2 : 1;

        $installments = [];
        $totalInterest = 0.0;

        if ($upfrontAmount > 0) {
            $installments[] = [
                'installment_no' => 1,
                'principal_amount' => $upfrontAmount,
                'interest_amount' => 0.0,
                'total_due_amount' => $upfrontAmount,
                'due_at' => $baseDate->copy(),
                'status' => 'paid',
                'is_upfront' => true,
            ];
        }

        if ($financedAmount > 0 && $remainingInstallments > 0) {
            $principalPerInstallment = round($financedAmount / $remainingInstallments, 2);
            $distributedPrincipal = 0.0;

            for ($i = 0; $i < $remainingInstallments; $i++) {
                $installmentNo = $startNo + $i;
                $principal = ($i === $remainingInstallments - 1)
                    ? round($financedAmount - $distributedPrincipal, 2)
                    : $principalPerInstallment;

                $distributedPrincipal = round($distributedPrincipal + $principal, 2);
                $interest = $this->calculateInterest(
                    $principal,
                    (float) $split->interest_rate_amount,
                    (string) $split->interest_rate_type
                );
                $dueDate = $this->calculateDueDate(
                    $i + 1,
                    (int) $split->payment_interval_amount,
                    (string) $split->payment_interval_type,
                    $baseDate
                );

                $totalInterest = round($totalInterest + $interest, 2);

                $installments[] = [
                    'installment_no' => $installmentNo,
                    'principal_amount' => $principal,
                    'interest_amount' => $interest,
                    'total_due_amount' => round($principal + $interest, 2),
                    'due_at' => $dueDate,
                    'status' => 'pending',
                    'is_upfront' => false,
                ];
            }
        }

        return [
            'split_count' => $splitCount,
            'total_item_amount' => $orderAmount,
            'initial_paid_amount' => $upfrontAmount,
            'final_amount_to_pay' => $financedAmount,
            'total_fees' => $totalInterest,
            'total_payable' => round($orderAmount + $totalInterest, 2),
            'installments' => $installments,
        ];
    }
}
