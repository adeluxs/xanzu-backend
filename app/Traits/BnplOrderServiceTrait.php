<?php

namespace App\Traits;

use App\Enums\BnplLoanStatus;
use App\Enums\OrderStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Models\BnplInstallment;
use App\Models\BnplItemLoan;
use App\Models\CreditLimitSplit;
use App\Models\DepositMethod;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BnplScheduleService;
use App\Services\CreditLimitService;
use Exception;
use Illuminate\Support\Facades\DB;

trait BnplOrderServiceTrait
{
    public function processBnplOrder(Order $order, User $buyer, ?int $splitId = null, bool $skipUpfront = false): array
    {
        DB::beginTransaction();
        try {
            $order->loadMissing('items');

            if ($order->items->count() !== 1) {
                throw new Exception('BNPL allows only one item per order.');
            }

            $orderItem = $order->items->first();

            $splitQuery = CreditLimitSplit::query()->active();
            if ($splitId) {
                $splitQuery->whereKey($splitId);
            }
            $split = $splitQuery->first();
            if (!$split) {
                throw new Exception('No active BNPL split configuration found.');
            }

            $orderAmount = round((float) $order->total_price, 2);
            $takeInitialInstallment = (bool) setting('bnpl_take_initial_installment', 'permission');
            if ($skipUpfront) {
                $takeInitialInstallment = false;
            }

            $preview = app(BnplScheduleService::class)->buildSchedulePreview($split, $orderAmount, $takeInitialInstallment);
            $splitCount = (int) ($preview['split_count'] ?? 1);
            $upfrontAmount = round((float) ($preview['initial_paid_amount'] ?? 0), 2);
            $financedAmount = round((float) ($preview['final_amount_to_pay'] ?? 0), 2);

            $lockedBuyer = User::query()->whereKey($buyer->id)->lockForUpdate()->firstOrFail();

            if ($upfrontAmount > 0 && $lockedBuyer->balance < $upfrontAmount) {
                throw new Exception('Insufficient balance for BNPL initial installment.');
            }

            if ($lockedBuyer->remaining_credit_limit_amount < $financedAmount) {
                throw new Exception('Insufficient BNPL credit limit.');
            }

            if ($upfrontAmount > 0) {
                $lockedBuyer->decrement('balance', $upfrontAmount);
            }

            if ($financedAmount > 0) {
                $this->syncUserCreditLimitAfterUsedChange($lockedBuyer, $financedAmount);
            }

            $order->update([
                'is_bnpl' => true,
                'bnpl_upfront_amount' => $upfrontAmount,
            ]);

            // update order txn amount to reflect upfront payment
            if ($upfrontAmount > 0) {
                $orderTxnAmount = $upfrontAmount + $order->final_shipping_charge - $order->discount_amount;
                $order->transactions()->where('type', TxnType::ProductOrder->value)
                    ->update([
                        'amount' => $orderTxnAmount,
                        'pay_amount' => $orderTxnAmount,
                        'final_amount' => $orderTxnAmount,
                    ]);
            }

            $loanStatus = $financedAmount <= 0
                ? BnplLoanStatus::Paid->value
                : ($upfrontAmount > 0 ? BnplLoanStatus::PartiallyPaid->value : BnplLoanStatus::Pending->value);
            $loanId = DB::table('bnpl_item_loans')->insertGetId([
                'user_id' => $lockedBuyer->id,
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'credit_limit_split_id' => $split->id,
                'total_item_amount' => $orderAmount,
                'initial_paid_amount' => $upfrontAmount,
                'final_amount_to_pay' => $financedAmount,
                'remaining_due_amount' => $financedAmount,
                'total_split' => $splitCount,
                'payment_interval_amount' => (int) $split->payment_interval_amount,
                'payment_interval_type' => (string) $split->payment_interval_type,
                'interest_rate_amount' => (float) $split->interest_rate_amount,
                'interest_rate_type' => (string) $split->interest_rate_type,
                'delay_fine_amount' => (float) $split->delay_fine_amount,
                'delay_fine_type' => (string) $split->delay_fine_type,
                'status' => $loanStatus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($upfrontAmount > 0) {
                DB::table('bnpl_installments')->insert([
                    'bnpl_item_loan_id' => $loanId,
                    'installment_no' => 1,
                    'principal_amount' => $upfrontAmount,
                    'interest_amount' => 0,
                    'late_fee_amount' => 0,
                    'total_due_amount' => $upfrontAmount,
                    'paid_amount' => $upfrontAmount,
                    'due_at' => now(),
                    'paid_at' => now(),
                    'status' => 'paid',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            collect($preview['installments'] ?? [])
                ->filter(fn(array $installment) => !((bool) ($installment['is_upfront'] ?? false)))
                ->each(function (array $installment) use ($loanId) {
                    DB::table('bnpl_installments')->insert([
                        'bnpl_item_loan_id' => $loanId,
                        'installment_no' => (int) ($installment['installment_no'] ?? 0),
                        'principal_amount' => round((float) ($installment['principal_amount'] ?? 0), 2),
                        'interest_amount' => round((float) ($installment['interest_amount'] ?? 0), 2),
                        'late_fee_amount' => 0,
                        'total_due_amount' => round((float) ($installment['total_due_amount'] ?? 0), 2),
                        'paid_amount' => 0,
                        'due_at' => $installment['due_at'] ?? now(),
                        'paid_at' => null,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

            DB::commit();

            return [
                'loan_id' => $loanId,
                'initial_paid_amount' => $upfrontAmount,
                'final_amount_to_pay' => $financedAmount,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function releaseBnplCredit(Order $order, string $event = 'release'): void
    {
        if (!($order->is_bnpl ?? false)) {
            return;
        }

        $loan = BnplItemLoan::query()
            ->where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if (!$loan) {
            return;
        }

        if ($loan->status === BnplLoanStatus::Cancelled->value) {
            return;
        }

        $paidPrincipal = BnplInstallment::query()
            ->where('bnpl_item_loan_id', $loan->id)
            ->where('status', 'paid')
            ->when((float) $loan->initial_paid_amount > 0, function ($query) {
                $query->where('installment_no', '>', 1);
            })
            ->sum('principal_amount');

        $releasePrincipal = max(0, round((float) $loan->final_amount_to_pay - (float) $paidPrincipal, 2));
        if ($releasePrincipal > 0) {
            $buyer = User::query()->whereKey($order->buyer_id)->lockForUpdate()->first();
            if ($buyer) {
                $this->syncUserCreditLimitAfterUsedChange($buyer, -$releasePrincipal);
            }
        }

        $loan->update([
            'remaining_due_amount' => 0,
            'status' => BnplLoanStatus::Cancelled->value,
        ]);

        BnplInstallment::query()
            ->where('bnpl_item_loan_id', $loan->id)
            ->whereIn('status', ['pending', 'processing', 'overdue'])
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
    }

    public function payBnplInstallment(User $buyer, Order $order, int $orderItemId, int $installmentId): BnplItemLoan
    {
        DB::beginTransaction();
        try {
            $loan = BnplItemLoan::query()
                ->where('user_id', $buyer->id)
                ->where('order_id', $order->id)
                ->where('order_item_id', $orderItemId)
                ->lockForUpdate()
                ->firstOrFail();

            $installment = BnplInstallment::query()
                ->where('bnpl_item_loan_id', $loan->id)
                ->whereKey($installmentId)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($installment->status, ['paid', 'cancelled', 'processing'], true)) {
                throw new Exception('Installment is not payable.');
            }

            $dueAmount = round((float) $installment->total_due_amount - (float) $installment->paid_amount, 2);
            if ($dueAmount <= 0) {
                throw new Exception('No due amount found for this installment.');
            }

            $lockedBuyer = User::query()->whereKey($buyer->id)->lockForUpdate()->firstOrFail();
            if ((float) $lockedBuyer->balance < $dueAmount) {
                throw new Exception('Insufficient balance for BNPL installment payment.');
            }

            $principalOutstanding = round(max(0, (float) $installment->principal_amount - min((float) $installment->paid_amount, (float) $installment->principal_amount)), 2);

            $lockedBuyer->decrement('balance', $dueAmount);

            if ($principalOutstanding > 0) {
                $this->syncUserCreditLimitAfterUsedChange($lockedBuyer, -$principalOutstanding);
            }

            $installment->update([
                'paid_amount' => round((float) $installment->paid_amount + $dueAmount, 2),
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $loan->remaining_due_amount = max(0, round((float) $loan->remaining_due_amount - $principalOutstanding, 2));

            $hasPendingInstallments = BnplInstallment::query()
                ->where('bnpl_item_loan_id', $loan->id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->exists();

            $loan->status = $hasPendingInstallments ? BnplLoanStatus::PartiallyPaid->value : BnplLoanStatus::Paid->value;
            $loan->save();
            $this->syncBnplOrderPaymentStatus($order, $loan);

            Transaction::create([
                'user_id' => $buyer->id,
                'order_id' => $order->id,
                'description' => 'BNPL installment payment: ' . $order->getProductNames(),
                'amount' => $dueAmount,
                'charge' => 0,
                'type' => TxnType::Subtract->value,
                'status' => TxnStatus::Success->value,
                'pay_currency' => setting('site_currency', 'global'),
                'pay_amount' => $dueAmount,
                'final_amount' => $dueAmount,
                'method' => 'balance',
            ]);

            DB::commit();

            return $loan->fresh('installments');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createBnplInstallmentGatewayTransaction(
        User $buyer,
        Order $order,
        int $orderItemId,
        int $installmentId,
        string $gatewayCode,
        array $manualFieldData = []
    ): Transaction {
        $gatewayInfo = DepositMethod::where('gateway_code', $gatewayCode)->first();
        if (!$gatewayInfo) {
            throw new Exception('Invalid payment gateway.');
        }

        DB::beginTransaction();
        try {
            $loan = BnplItemLoan::query()
                ->where('user_id', $buyer->id)
                ->where('order_id', $order->id)
                ->where('order_item_id', $orderItemId)
                ->lockForUpdate()
                ->first();

            throw_if(!$loan, fn() => new Exception('BNPL item loan not found.'));

            $installment = BnplInstallment::query()
                ->where('bnpl_item_loan_id', $loan->id)
                ->whereKey($installmentId)
                ->lockForUpdate()
                ->first();

            throw_if(!$installment, fn() => new Exception('BNPL installment not found.'));

            if (in_array($installment->status, ['paid', 'cancelled', 'processing'], true)) {
                throw new Exception('Installment is not payable right now.');
            }

            $hasPendingRequest = Transaction::query()
                ->where('type', TxnType::BnplInstallment->value)
                ->where('target_type', 'bnpl_installment')
                ->where('target_id', $installment->id)
                ->where('status', TxnStatus::Pending->value)
                ->exists();

            if ($hasPendingRequest) {
                throw new Exception('This installment already has a pending payment request.');
            }

            $dueAmount = round((float) $installment->total_due_amount - (float) $installment->paid_amount, 2);
            if ($dueAmount <= 0) {
                throw new Exception('No due amount found for this installment.');
            }

            [$payAmount, $charge, $finalAmount, $methodInfo] = gatewayPayAmount($gatewayCode, $dueAmount);

            $installment->status = BnplLoanStatus::Processing->value;
            $installment->save();

            $transaction = Transaction::create([
                'user_id' => $buyer->id,
                'order_id' => $order->id,
                'description' => 'BNPL installment #' . $installment->installment_no . ' for ' . $order->getProductNames(),
                'amount' => $dueAmount,
                'charge' => $charge,
                'type' => TxnType::BnplInstallment->value,
                'status' => TxnStatus::Pending->value,
                'pay_currency' => $methodInfo?->currency ?? setting('site_currency', 'global'),
                'pay_amount' => $payAmount,
                'final_amount' => $finalAmount,
                'manual_field_data' => json_encode($manualFieldData),
                'method' => $gatewayCode,
                'target_id' => $installment->id,
                'target_type' => 'bnpl_installment',
            ]);

            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markBnplInstallmentTransactionPaid(Transaction $transaction): BnplItemLoan
    {
        if ($transaction->type !== TxnType::BnplInstallment || $transaction->target_type !== 'bnpl_installment') {
            throw new Exception('Invalid BNPL installment transaction.');
        }

        DB::beginTransaction();
        try {
            $installment = BnplInstallment::query()
                ->whereKey($transaction->target_id)
                ->lockForUpdate()
                ->firstOrFail();

            $loan = BnplItemLoan::query()
                ->whereKey($installment->bnpl_item_loan_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($installment->status, ['paid', 'cancelled'], true)) {
                $this->syncBnplOrderPaymentStatus($loan->order, $loan);
                DB::commit();

                return $loan->fresh('installments');
            }

            $dueAmount = round((float) $installment->total_due_amount - (float) $installment->paid_amount, 2);
            if ($dueAmount <= 0) {
                $installment->status = 'paid';
                $installment->paid_at = $installment->paid_at ?? now();
                $installment->save();
                $this->syncBnplOrderPaymentStatus($loan->order, $loan);

                DB::commit();

                return $loan->fresh('installments');
            }

            $principalOutstanding = round(max(0, (float) $installment->principal_amount - min((float) $installment->paid_amount, (float) $installment->principal_amount)), 2);

            $installment->paid_amount = round((float) $installment->paid_amount + $dueAmount, 2);
            $installment->status = 'paid';
            $installment->paid_at = now();
            $installment->save();

            if ($principalOutstanding > 0) {
                $buyer = User::query()->whereKey($loan->user_id)->lockForUpdate()->first();
                if ($buyer) {
                    $this->syncUserCreditLimitAfterUsedChange($buyer, -$principalOutstanding);
                }
            }

            $loan->remaining_due_amount = max(0, round((float) $loan->remaining_due_amount - $principalOutstanding, 2));
            $hasPendingInstallments = BnplInstallment::query()
                ->where('bnpl_item_loan_id', $loan->id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->exists();
            $loan->status = $hasPendingInstallments ? BnplLoanStatus::PartiallyPaid->value : BnplLoanStatus::Paid->value;
            $loan->save();
            $this->syncBnplOrderPaymentStatus($loan->order, $loan);

            DB::commit();

            return $loan->fresh('installments');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markBnplInstallmentTransactionRejected(Transaction $transaction): void
    {
        if ($transaction->type !== TxnType::BnplInstallment || $transaction->target_type !== 'bnpl_installment') {
            return;
        }

        DB::beginTransaction();
        try {
            $installment = BnplInstallment::query()
                ->whereKey($transaction->target_id)
                ->lockForUpdate()
                ->first();

            if (!$installment || $installment->status !== 'processing') {
                DB::commit();

                return;
            }

            $installment->status = $installment->due_at && $installment->due_at->isPast() ? 'overdue' : 'pending';
            $installment->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function syncUserCreditLimitAfterUsedChange(User $user, float $usedDelta): void
    {
        app(CreditLimitService::class)->adjustUsed($user, $usedDelta);
    }

    private function syncBnplOrderPaymentStatus(?Order $order, ?BnplItemLoan $loan = null): void
    {
        if (!$order || !($order->is_bnpl ?? false)) {
            return;
        }

        $loan = $loan?->exists ? $loan : BnplItemLoan::query()->where('order_id', $order->id)->first();
        if (!$loan) {
            return;
        }

        $hasPaidInstallment = BnplInstallment::query()
            ->where('bnpl_item_loan_id', $loan->id)
            ->where('status', BnplLoanStatus::Paid->value)
            ->exists();

        $hasUnsettledInstallments = BnplInstallment::query()
            ->where('bnpl_item_loan_id', $loan->id)
            ->whereNotIn('status', [BnplLoanStatus::Paid->value, BnplLoanStatus::Cancelled->value])
            ->exists();

        $paymentStatus = match (true) {
            !$hasUnsettledInstallments && $loan->status === BnplLoanStatus::Paid->value => TxnStatus::Success->value,
            (float) $loan->initial_paid_amount > 0 || $hasPaidInstallment => TxnStatus::PartiallyPaid->value,
            default => TxnStatus::Pending->value,
        };

        $order->update([
            'payment_status' => $paymentStatus,
            'status' => (!$hasUnsettledInstallments && $loan->status === BnplLoanStatus::Paid->value)
                ? OrderStatus::Completed->value
                : $order->status,
        ]);
    }
}
