<?php

namespace App\Services;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\User;
use App\Traits\NotifyTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SendMoneyService
{
    use NotifyTrait;

    public function validate(array $data, bool $isAgent = false): void
    {
        $sender = auth()->user();

        Log::info('SendMoneyService@validate: Starting validation', [
            'user_id' => $sender?->id,
            'is_agent' => $isAgent,
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'amount' => $data['amount'] ?? null,
        ]);

        $rules = [
            'recipient_phone' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            Log::warning('SendMoneyService@validate: Basic validation failed', [
                'user_id' => $sender?->id,
                'errors' => $validator->errors()->all(),
            ]);
            throw new ValidationException($validator, $validator->errors()->first());
        }

        $normalizedPhone = $this->normalizePhone($data['recipient_phone']);
        $data['recipient_phone'] = $normalizedPhone;

        Log::info('SendMoneyService@validate: Phone normalized', [
            'user_id' => $sender?->id,
            'original_phone' => $data['recipient_phone'] ?? null,
            'normalized_phone' => $normalizedPhone,
        ]);

        $recipient = User::where('phone', $normalizedPhone)->first();

        if (!$recipient) {
            Log::warning('SendMoneyService@validate: Recipient not found', [
                'user_id' => $sender?->id,
                'normalized_phone' => $normalizedPhone,
            ]);
            throw ValidationException::withMessages(['recipient_phone' => __('Recipient not found.')]);
        }

        if ($recipient->status != 1) {
            Log::warning('SendMoneyService@validate: Recipient account not active', [
                'user_id' => $sender?->id,
                'recipient_id' => $recipient->id,
                'recipient_status' => $recipient->status,
            ]);
            throw ValidationException::withMessages(['recipient_phone' => __('Recipient account is not active.')]);
        }

        if ($sender->id == $recipient->id) {
            Log::warning('SendMoneyService@validate: Self-transfer attempt', [
                'user_id' => $sender?->id,
            ]);
            throw ValidationException::withMessages(['recipient_phone' => __('You cannot send money to yourself.')]);
        }

        if (!$isAgent && !$sender->transfer_status) {
            Log::warning('SendMoneyService@validate: Transfer disabled for user', [
                'user_id' => $sender?->id,
                'transfer_status' => $sender->transfer_status,
            ]);
            throw ValidationException::withMessages(['transfer' => __('Transfer is not enabled for your account.')]);
        }

        $amount = (float) $data['amount'];

        if ($sender->balance < $amount) {
            Log::warning('SendMoneyService@validate: Insufficient balance', [
                'user_id' => $sender?->id,
                'balance' => $sender->balance,
                'requested_amount' => $amount,
            ]);
            throw ValidationException::withMessages(['amount' => __('Insufficient balance.')]);
        }

        Log::info('SendMoneyService@validate: Validation passed', [
            'user_id' => $sender?->id,
            'recipient_id' => $recipient->id,
            'amount' => $amount,
        ]);
    }

    public function normalizePhone(string $phone): string
    {
        if (str_starts_with($phone, '+')) {
            Log::info('SendMoneyService@normalizePhone: Phone already has country code', [
                'phone' => $phone,
            ]);
            return $phone;
        }

        $sender = auth()->user();
        if ($sender && $sender->country) {
            $dialCode = getCountryData($sender->country, 'dial_code');
            if ($dialCode) {
                Log::info('SendMoneyService@normalizePhone: Prepend sender country dial code', [
                    'user_id' => $sender->id,
                    'country' => $sender->country,
                    'dial_code' => $dialCode,
                    'original_phone' => $phone,
                    'normalized_phone' => $dialCode . $phone,
                ]);
                return $dialCode . $phone;
            }
        }

        $normalized = '+' . ltrim($phone, '0');
        Log::info('SendMoneyService@normalizePhone: Fallback normalization', [
            'user_id' => $sender?->id,
            'original_phone' => $phone,
            'normalized_phone' => $normalized,
        ]);

        return $normalized;
    }

    public function lookupRecipient(string $phone): ?array
    {
        $normalizedPhone = $this->normalizePhone($phone);

        Log::info('SendMoneyService@lookupRecipient: Searching for recipient', [
            'user_id' => auth()->id(),
            'original_phone' => $phone,
            'normalized_phone' => $normalizedPhone,
        ]);

        $recipient = User::where('phone', $normalizedPhone)
            ->orWhere('phone', 'LIKE', '%' . ltrim($phone, '0'))
            ->first();

        if (!$recipient) {
            Log::info('SendMoneyService@lookupRecipient: Recipient not found', [
                'user_id' => auth()->id(),
                'normalized_phone' => $normalizedPhone,
            ]);
            return null;
        }

        Log::info('SendMoneyService@lookupRecipient: Recipient found', [
            'user_id' => auth()->id(),
            'recipient_id' => $recipient->id,
            'recipient_phone' => $recipient->phone,
            'recipient_name' => $recipient->full_name,
        ]);

        return [
            'full_name' => $recipient->full_name,
            'first_name' => $recipient->first_name,
            'last_name' => $recipient->last_name,
            'phone' => $recipient->phone,
            'status' => $recipient->status,
        ];
    }

    public function sendMoney(array $data, bool $isAgent = false): array
    {
        $sender = auth()->user();

        Log::info('SendMoneyService@sendMoney: Starting transfer', [
            'user_id' => $sender?->id,
            'is_agent' => $isAgent,
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'amount' => $data['amount'] ?? null,
        ]);

        $this->validate($data, $isAgent);

        $recipientPhone = $this->normalizePhone($data['recipient_phone']);
        $recipient = User::where('phone', $recipientPhone)->first();
        $amount = round((float) $data['amount'], 2);
        $charge = 0;
        $finalAmount = $amount;

        Log::info('SendMoneyService@sendMoney: Validation passed, beginning transaction', [
            'user_id' => $sender->id,
            'sender_balance_before' => $sender->balance,
            'recipient_id' => $recipient->id,
            'recipient_balance_before' => $recipient->balance,
            'amount' => $amount,
            'final_amount' => $finalAmount,
        ]);

        DB::beginTransaction();

        try {
            $sender->balance -= $finalAmount;
            $sender->save();

            $recipient->balance += $finalAmount;
            $recipient->save();

            Log::info('SendMoneyService@sendMoney: Balances updated', [
                'user_id' => $sender->id,
                'sender_balance_after' => $sender->balance,
                'recipient_id' => $recipient->id,
                'recipient_balance_after' => $recipient->balance,
            ]);

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

            Log::info('SendMoneyService@sendMoney: Transaction committed successfully', [
                'user_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'amount' => $amount,
            ]);

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

            Log::error('SendMoneyService@sendMoney: Transaction failed', [
                'user_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'amount' => $amount,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

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
