<?php

namespace App\Services;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Models\Transaction;
use App\Models\WithdrawAccount;
use App\Models\WithdrawalSchedule;
use App\Traits\NotifyTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WithdrawService
{
    use NotifyTrait;

    public function validate(array $data)
    {
        $rules = [
            'withdraw_account' => 'required|exists:withdraw_accounts,id',
            'amount' => 'required|numeric|min:1',
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator, response($validator->errors()->first()));
        }
    }

    public function withdrawMoney($withdrawAccount, float $amount)
    {
        $withdrawOffDays = WithdrawalSchedule::where('status', false)->pluck('name')->toArray();

        $date = Carbon::now();
        $user = Auth::user();

        $today = $date->format('l');

        if (in_array($today, $withdrawOffDays)) {
            throw ValidationException::withMessages(['withdraw' => __('Today is the off day of withdraw')]);
        }

        $todayTransaction = Transaction::query()
            ->whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto])
            ->where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $amount = (float) $amount;

        $withdrawAccount = WithdrawAccount::with('method')->find($withdrawAccount);

        $withdrawMethod = $withdrawAccount->method;

        if ($amount < $withdrawMethod->min_withdraw || $amount > $withdrawMethod->max_withdraw) {
            $message = __('Please ensure the withdrawal amount is between :min to :max', [
                'min' => amountWithCurrency($withdrawMethod->min_withdraw),
                'max' => amountWithCurrency($withdrawMethod->max_withdraw),
            ]);

            throw ValidationException::withMessages(['amount' => $message]);
        }

        $charge = $withdrawMethod->charge_type == 'percentage' ? (($withdrawMethod->charge / 100) * $amount) : $withdrawMethod->charge;

        $totalAmount = $amount + (float) $charge;

        if ($user->balance < $totalAmount) {
            throw ValidationException::withMessages(['amount' => __('Insufficient Balance')]);
        }

        try {
            DB::beginTransaction();

            $user->balance -= $totalAmount;
            $user->save();

            $payAmount = $amount * $withdrawMethod->rate;

            $type = $withdrawMethod->type == 'auto' ? TxnType::WithdrawAuto : TxnType::Withdraw;

            $txnInfo = Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'charge' => $charge,
                'final_amount' => $totalAmount,
                'description' => 'Withdraw With '.$withdrawAccount->method_name,
                'type' => $type,
                'status' => TxnStatus::Pending,
                'pay_amount' => $payAmount,
                'pay_currency' => $withdrawMethod->currency,
                'method' => $withdrawMethod->name,
                'manual_field_data' => ($withdrawAccount->credentials),
            ]);

            DB::commit();

            $shortcodes = [
                '[[amount]]' => amountWithCurrency($txnInfo->amount),
                '[[charge]]' => amountWithCurrency($txnInfo->charge),
                '[[gateway]]' => $txnInfo->method,
                '[[request_at]]' => $txnInfo->created_at,
                '[[total_amount]]' => amountWithCurrency($txnInfo->final_amount),
                '[[request_link]]' => route('admin.withdraw.pending'),
                '[[site_title]]' => setting('site_title', 'global'),
                '[[currency]]' => setting('site_currency', 'global'),
            ];

            $this->sendNotify($user->email, 'admin_withdraw_request', 'Admin', $shortcodes, $user->phone, $user->id, route('admin.withdraw.pending'));

            return $txnInfo;
        } catch (\Throwable $throwable) {

            DB::rollBack();
            throw ValidationException::withMessages(['error' => __('Sorry! Something went wrong.')]);
        }

    }
}
