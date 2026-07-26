<?php

namespace App\Traits;

use App\Enums\PlanHistoryStatus;
use App\Enums\ReferralType;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\PlanHistory;

trait PlanTrait
{
    public function executePlanPurchaseProcess($user, $plan, $transaction = null)
    {
        // Check if the user already has an active plan of the same id
        $existingPlanHistory = PlanHistory::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', PlanHistoryStatus::ACTIVE)
            ->first();
        if ($existingPlanHistory) {
            // Extend the limits and validity of the existing plan
            $existingPlanHistory->referral_level += $plan->referral_level;
            $existingPlanHistory->validity_at = now()->parse($existingPlanHistory->validity_at)->addDays((int) $plan->validity);
            $existingPlanHistory->status = PlanHistoryStatus::ACTIVE;
            $existingPlanHistory->save();
        } else {

            // refund any active plan exists
            $existsPlan = PlanHistory::where('user_id', $user->id)
                ->where('status', PlanHistoryStatus::ACTIVE)->get();

            if ($existsPlan->count()) {
                foreach ($existsPlan as $e_plan) {
                    $remainingDays = now()->parse($e_plan->validity_at)->diffInDays();
                    $refundAmount = ($e_plan->amount / $e_plan->plan->validity) * $remainingDays;
                    $e_plan->status = PlanHistoryStatus::EXPIRED;
                    $e_plan->save();

                    if ($remainingDays > 0) {
                        $user->increment('balance', $refundAmount);
                        (new Txn)->new($refundAmount, 0, $refundAmount, 'System', "Refund for {$remainingDays} days left on {$e_plan?->plan?->name} due to new plan subscription", TxnType::PlanRefund, TxnStatus::Success, null, null, $user->id, $e_plan->id);
                    }

                }
            }

            $user->refresh();

            // Create a new plan history record if no active plan exists
            $history = PlanHistory::create([
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'referral_level' => $plan->referral_level,
                'withdraw_limit' => $plan->withdraw_limit,
                'listing_limit' => $plan->listing_limit,
                'flash_sale_limit' => $plan->flash_sale_limit,
                'amount' => $plan->price,
                'validity_at' => now()->addDays((int) $plan->validity),
                'status' => PlanHistoryStatus::ACTIVE,
                'charge_value' => $plan->charge_value,
                'charge_type' => $plan->charge_type,
            ]);

            $user->update(['current_plan_id' => $history->id, 'plan_id' => $plan->id]);
        }

        // Credit referral bonus if enabled
        if (setting('subscription_plan_level') && $transaction !== null) {
            $level = getReferralLevel($transaction->user_id);
            creditReferralBonus($transaction->user, ReferralType::SubscriptionPlan->value, $transaction->amount, $level);
        }
        orderService()->dismissSession();
    }
}
