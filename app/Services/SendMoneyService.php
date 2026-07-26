<?php

namespace App\Services;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\User;
use App\Traits\NotifyTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SendMoneyService
{
    use NotifyTrait;

    public function validate(array $data, bool $isAgent = false): void
    {
        $rules = [
            'recipient_phone' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator, $validator->errors()->first());
        }

        $recipient = User::where('phone', $data['recipient_phone'])->first();

        if (!$recipient) {
            throw ValidationException::withMessages(['recipient_phone' => __('Recipient not found.')]);
        }

        if ($recipient->status != 1) {
            throw ValidationException::withMessages(['recipient_phone' => __('Recipient account is not active.')]);
        }

        $sender = auth()->user();

        if ($sender->id == $recipient->id) {
            throw ValidationException::withMessages(['recipient_phone' => __('You cannot send money to yourself.')]);
        }

        if (!$isAgent && !$sender->transfer_status) {
            throw ValidationException::withMessages(['transfer' => __('Transfer is not enabled for your account.')]);
        }

        $amount = (float) $data['amount'];

        if ($sender->balance < $amount) {
            throw ValidationException::withMessages(['amount' => __('Insufficient balance.')]);
        }
    }

    public function sendMoney(array $data, bool $isAgent = false): array
    {
        $this->validate($data, $isAgent);

        $sender = auth()->user();
        $recipient = User::where('phone', $data['recipient_phone'])->first();
        $amount = round((float) $data['amount'], 2);
        $charge = 0;
        $finalAmount = $amount;

        DB::beginTransaction();

        try {
            $sender->balance -= $finalAmount;
            $sender->save();

            $recipient->balance += $finalAmount;
            $recipient->save();

            $description = 'Transfer to ' . $recipient->full_name;

            (new Txn)->new(
                $amount,
                $charge,
                $finalAmount,
                'Wallet Transfer',
                $description,
                TxnType::Transfer,
                TxnStatus::Success,
                setting('site_currency', 'global'),
                $finalAmount,
                $sender->id,
                $recipient->id,
                'User',
                [],
                'none',
                null,
                null,
                false,
                null
            );

            $recipientDescription = 'Received from ' . $sender->full_name;

            (new Txn)->new(
                $amount,
                $charge,
                $finalAmount,
                'Wallet Transfer',
                $recipientDescription,
                TxnType::Transfer,
                TxnStatus::Success,
                setting('site_currency', 'global'),
                $finalAmount,
                $recipient->id,
                $sender->id,
                'User',
                [],
                'none',
                null,
                null,
                false,
                null
            );

            DB::commit();

            $this->sendTransferNotifications($sender, $recipient, $amount);

            return [
                'success' => true,
                'message' => __('Transfer successful.'),
                'transaction' => [
                    'amount' => $amount,
                    'recipient' => $recipient->full_name,
                    'recipient_phone' => $recipient->phone,
                ],
            ];
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }
    }

    private function sendTransferNotifications(User $sender, User $recipient, float $amount): void
    {
        $senderShortcodes = [
            '[[amount]]' => amountWithCurrency($amount),
            '[[recipient]]' => $recipient->full_name,
            '[[recipient_phone]]' => $recipient->phone,
            '[[site_title]]' => setting('site_title', 'global'),
        ];

        $this->sendNotify(
            $sender->email,
            'transfer_sent',
            'User',
            $senderShortcodes,
            $sender->phone,
            $sender->id
        );

        $recipientShortcodes = [
            '[[amount]]' => amountWithCurrency($amount),
            '[[sender]]' => $sender->full_name,
            '[[sender_phone]]' => $sender->phone,
            '[[site_title]]' => setting('site_title', 'global'),
        ];

        $this->sendNotify(
            $recipient->email,
            'transfer_received',
            'User',
            $recipientShortcodes,
            $recipient->phone,
            $recipient->id
        );
    }
}
