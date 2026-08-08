<?php

namespace App\Http\Controllers;

use App\Enums\BnplLoanStatus;
use App\Enums\KYCStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Models\BnplInstallment;
use App\Models\Category;
use App\Models\CreditLimit;
use App\Models\CronJob;
use App\Models\CronJobLog;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Models\UserCreditLimitHistory;
use App\Traits\NotifyTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Remotelywork\Installer\Repository\App;
use Throwable;

class CronJobController extends Controller
{
    use NotifyTrait;

    public function runCronJobs()
    {

        $action_id = request('run_action');

        if (is_null($action_id)) {
            $jobs = CronJob::where('status', 'running')
                ->where('next_run_at', '<', now())
                ->get();
        } else {
            $jobs = CronJob::whereKey($action_id)->get();
        }

        foreach ($jobs as $job) {

            $error = null;

            $log = new CronJobLog;
            $log->cron_job_id = $job->id;
            $log->started_at = now();

            try {

                if ($job->type == 'system') {
                    $this->{$job->reserved_method}();
                } else {
                    Http::withOptions([
                        'verify' => false,
                    ])->get($job->url);
                }
            } catch (Throwable $th) {
                $error = $th->getMessage();
            }

            $log->ended_at = now();
            $log->error = $error;
            $log->save();

            $job->update([
                'last_run_at' => now(),
                'next_run_at' => now()->addSeconds($job->schedule),
            ]);
        }

        if ($action_id !== null) {
            notify()->success(__('Cron running successfully!'), 'Success');

            return back();
        }
    }

    public function userInactive()
    {
        if (!setting('inactive_account_disabled', 'inactive_user') == 1) {
            return false;
        }

        try {

            DB::beginTransaction();
            $this->startCron();

            User::whereDoesntHave('activities', function ($query) {
                $query->where('created_at', '>', now()->subDays(30));
            })->where('status', 1)->chunk(500, function ($inactiveUsers) {
                foreach ($inactiveUsers as $user) {
                    $user->update(['status' => 0]);
                    $shortcodes = [
                        '[[full_name]]' => $user->full_name,
                        '[[site_title]]' => setting('site_title', 'global'),
                        '[[site_url]]' => route('home'),
                        '[[inactive_days]]' => setting('inactive_days', 'inactive_user'),
                    ];
                    $this->sendNotify($user->email, 'user_account_disabled', 'User', $shortcodes, $user->phone, $user->id);
                }
            });

            DB::commit();

            return '........Inactive users disabled successfully.';
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function startCron()
    {
        if (!App::initApp()) {
            return false;
        }
    }

    public function userCreditLimit()
    {
        try {
            DB::beginTransaction();
            $this->startCron();

            User::where('status', true)->when(setting('kyc_credit_limit'), function ($q) {
                $q->where('kyc', KYCStatus::Verified->value);
            })->chunk(500, function ($users) {
                foreach ($users as $user) {
                    $this->processCreditLimit($user);
                }
            });

            DB::commit();

            return '......User credit limit assignment completed successfully!';
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function processCreditLimit(User $user, $withKyc = false)
    {
        $creditLimits = once(function () use ($withKyc) {
            return CreditLimit::active()
                ->where('is_kyc', $withKyc)
                ->orderBy('minimum_transactions', 'asc')
                ->get();
        });

        if ($creditLimits->isEmpty()) {
            return false;
        }

        // Calculate total successful transactions (excluding subtracts, refunds)
        $totalSuccessfulTxns = $user->transaction()
            ->where('status', TxnStatus::Success)
            ->whereNotIn('type', [TxnType::Subtract, TxnType::Refund, TxnType::OrderRefunded])
            ->sum('amount');

        // Determine eligible credit limits
        $eligibleLimits = $creditLimits->filter(fn($creditLimit) => $totalSuccessfulTxns >= $creditLimit->minimum_transactions);

        if ($eligibleLimits->isEmpty()) {
            return false;
        }

        // Pick the highest eligible credit limit by credit_amount
        $highestLimit = $eligibleLimits->sortByDesc('credit_amount')->first();

        // Skip if user already has this credit limit assigned
        if ($user->current_credit_limit_id == $highestLimit->id) {
            return false;
        }

        // Assign the new credit limit to the user while preserving current usage.
        $currentUsed = round((float) ($user->used_credit_limit_amount ?? 0), 2);
        $newLimit = round((float) $highestLimit->credit_amount, 2);
        $normalizedUsed = min(max($currentUsed, 0), $newLimit);
        $normalizedRemaining = max(round($newLimit - $normalizedUsed, 2), 0);

        $user->update([
            'current_credit_limit_id' => $highestLimit->id,
            'credit_limit_amount' => $newLimit,
            'used_credit_limit_amount' => $normalizedUsed,
            'remaining_credit_limit_amount' => $normalizedRemaining,
        ]);

        // Record in history table
        UserCreditLimitHistory::create([
            'user_id' => $user->id,
            'credit_limit_id' => $highestLimit->id,
            'for' => 'transaction',
            'threshold_amount' => $highestLimit->minimum_transactions,
            'credit_amount' => $highestLimit->credit_amount,
        ]);

        // Send notification to user
        $shortcodes = [
            '[[full_name]]' => $user->full_name,
            '[[credit_level]]' => $highestLimit->level,
            '[[credit_amount]]' => $highestLimit->credit_amount,
        ];
        $this->sendNotify($user->email, 'credit_limit_assigned', 'User', $shortcodes, $user->phone, $user->id);

        return true;
    }

    public function trendingAndPopularSet()
    {
        try {
            DB::beginTransaction();
            $this->startCron();

            Listing::public()->update(['is_trending' => false]);

            Category::query()->update(['is_trending' => false]);

            User::query()->update(['is_popular' => false]);

            $lastWeek = now()->subWeek();

            $lastWeekOrders = Order::where('created_at', '>=', $lastWeek);

            $lastWeekTrendingListings = (clone $lastWeekOrders)->groupBy('listing_id')->selectRaw('listing_id, count(*) as count')->orderBy('count', 'desc')->take(10)->pluck('listing_id', 'count');

            $lastWeekTrendingCategories = (clone $lastWeekOrders)->groupBy('category_id')->selectRaw('category_id, count(*) as count')->orderBy('count', 'desc')->take(10)->pluck('category_id', 'count');

            $lastWeekTrendingUsers = (clone $lastWeekOrders)->groupBy('seller_id')->selectRaw('seller_id, count(*) as count')->orderBy('count', 'desc')->take(10)->pluck('seller_id', 'count');

            Listing::whereIn('id', $lastWeekTrendingListings->values()->toArray())->update(['is_trending' => true]);

            Category::whereIn('id', $lastWeekTrendingCategories->values()->toArray())->update(['is_trending' => true]);

            User::where('user_type', 'merchant')->whereIn('id', $lastWeekTrendingUsers->values()->toArray())->update(['is_popular' => true]);
            DB::commit();

            return '......Trending and popular listings, categories and users set successfully!';
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function bnplInstallmentDueAndLateFee()
    {
        try {
            DB::beginTransaction();
            $this->startCron();

            $now = now();

            BnplInstallment::query()
                ->with('loan')
                ->whereIn('status', ['pending', 'partial'])
                ->where('due_at', '<', $now)
                ->whereHas('loan', function ($query) {
                    $query->whereNotIn('status', [BnplLoanStatus::Cancelled->value, BnplLoanStatus::Paid->value]);
                })
                ->orderBy('id')
                ->chunkById(500, function ($installments) {
                    foreach ($installments as $installment) {
                        $loan = $installment->loan;
                        if (!$loan) {
                            continue;
                        }

                        $currentLateFee = (float) $installment->late_fee_amount;
                        $lateFeeToAdd = 0.0;

                        if ($currentLateFee <= 0) {
                            $delayFineAmount = (float) $loan->delay_fine_amount;

                            if ($delayFineAmount > 0) {
                                if ($loan->delay_fine_type === 'percentage') {
                                    $lateFeeToAdd = round(((float) $installment->total_due_amount * $delayFineAmount) / 100, 2);
                                } else {
                                    $lateFeeToAdd = round($delayFineAmount, 2);
                                }
                            }
                        }

                        $installment->status = 'overdue';
                        $installment->late_fee_amount = round($currentLateFee + $lateFeeToAdd, 2);
                        $installment->total_due_amount = round((float) $installment->total_due_amount + $lateFeeToAdd, 2);
                        $installment->save();

                        $loan->status = BnplLoanStatus::Overdue->value;
                        if ($lateFeeToAdd > 0) {
                            $loan->remaining_due_amount = round((float) $loan->remaining_due_amount + $lateFeeToAdd, 2);
                        }
                        $loan->save();
                    }
                });

            DB::commit();

            return '......BNPL installments due status and late fees processed successfully!';
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
