<?php

namespace App\Services;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\NotifyTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SendMoneyService
{
    use NotifyTrait;

    public function __construct(private TransferLimitService $transferLimitService) {}

    public function validate(array $data, bool $isAgent = false): array
    {
        $sender = auth()->user();
        if (! $sender) {
            throw ValidationException::withMessages(['transfer' => __('Unauthorized.')]);
        }

        $this->validateFeatureAccess($sender);

        $validator = Validator::make($data, [
            'recipient_phone' => ['required', 'string', 'max:40'],
            'recipient_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'client_reference' => ['nullable', 'string', 'max:80'],
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator, $validator->errors()->first());
        }

        $amount = round((float) $data['amount'], 2);
        $recipient = $this->resolveRecipient(
            (string) $data['recipient_phone'],
            isset($data['recipient_id']) ? (int) $data['recipient_id'] : null
        );

        $this->validateRecipient($sender, $recipient);

        if ((float) $sender->balance < $amount) {
            throw ValidationException::withMessages(['amount' => __('Insufficient balance.')]);
        }

        // Validation and transfer use exactly the same policy. This prevents
        // the mobile validation step from passing only for the final request to
        // fail because daily/monthly limits were checked later.
        $this->transferLimitService->enforce($sender, $amount);

        return [
            'amount' => $amount,
            'recipient' => $recipient,
            'recipient_phone' => $recipient->phone,
        ];
    }

    public function normalizePhone(string $phone): string
    {
        return $this->phoneCandidates($phone)[0] ?? trim($phone);
    }

    public function lookupRecipient(string $phone): ?array
    {
        $recipient = $this->resolveRecipient($phone);
        if (! $recipient) {
            return null;
        }

        return [
            'id' => $recipient->id,
            'full_name' => $recipient->full_name,
            'first_name' => $recipient->first_name,
            'last_name' => $recipient->last_name,
            'phone' => $recipient->phone,
            'status' => $recipient->status,
        ];
    }

    public function transferConfig(User $user): array
    {
        $summary = $this->transferLimitService->summary($user);
        unset($summary['limit_model']);

        $access = $this->transferAccess($user);

        return [
            'user_balance' => (float) $user->balance,
            'transfer_status' => $access['enabled'],
            'global_status' => $access['global_enabled'],
            'role_status' => $access['role_enabled'],
            'user_status' => $access['user_enabled'],
            'user_type' => $access['user_type'],
            'disabled_reason' => $access['disabled_reason'],
            'kyc_required' => $access['kyc_required'],
            'kyc_verified' => (bool) $user->kyc,
            'currency' => setting('site_currency', 'global'),
            'currency_symbol' => setting('currency_symbol', 'global'),
            'limits' => $summary,
        ];
    }

    public function sendMoney(array $data, bool $isAgent = false): array
    {
        $preflight = $this->validate($data, $isAgent);
        $senderId = (int) auth()->id();
        $recipientId = (int) $preflight['recipient']->id;
        $amount = (float) $preflight['amount'];
        $clientReference = trim((string) ($data['client_reference'] ?? ''));

        if ($clientReference === '') {
            $clientReference = 'P2P-'.$senderId.'-'.now()->format('YmdHisv').'-'.bin2hex(random_bytes(4));
        }

        $result = DB::transaction(function () use ($senderId, $recipientId, $amount, $clientReference) {
            // Lock in deterministic id order to serialize concurrent balance
            // mutations and minimize deadlocks.
            $users = User::query()
                ->whereIn('id', [$senderId, $recipientId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /** @var User|null $sender */
            $sender = $users->get($senderId);
            /** @var User|null $recipient */
            $recipient = $users->get($recipientId);

            if (! $sender || ! $recipient) {
                throw ValidationException::withMessages(['recipient_phone' => __('Recipient not found.')]);
            }

            $this->validateFeatureAccess($sender);
            $this->validateRecipient($sender, $recipient);

            // Idempotency: a retry/double tap with the same client reference
            // returns the already committed sender transaction instead of
            // moving funds twice.
            $existing = Transaction::query()
                ->where('user_id', $sender->id)
                ->where('type', TxnType::Transfer->value)
                ->where('approval_cause', $clientReference)
                ->first();

            if ($existing) {
                $meta = json_decode((string) $existing->manual_field_data, true) ?: [];
                if ((int) ($meta['recipient_id'] ?? 0) !== $recipient->id || abs((float) $existing->amount - $amount) > 0.00001) {
                    throw ValidationException::withMessages([
                        'transfer' => __('This transfer reference has already been used for different transfer details.'),
                    ]);
                }

                return $this->formatTransferResult($existing, $recipient, true);
            }

            if ((float) $sender->balance < $amount) {
                throw ValidationException::withMessages(['amount' => __('Insufficient balance.')]);
            }

            // Re-check while the sender row is locked so two simultaneous
            // requests cannot both pass the same balance/limit snapshot.
            $this->transferLimitService->enforce($sender, $amount);

            $sender->balance = round((float) $sender->balance - $amount, 2);
            $recipient->balance = round((float) $recipient->balance + $amount, 2);
            $sender->save();
            $recipient->save();

            $currency = setting('site_currency', 'global');
            $meta = [
                'transfer_reference' => $clientReference,
                'recipient_id' => $recipient->id,
                'recipient_phone' => $recipient->phone,
            ];

            $senderTxn = (new Txn)->new(
                $amount,
                0,
                $amount,
                'Wallet Transfer',
                'Transfer to '.$recipient->full_name,
                TxnType::Transfer,
                TxnStatus::Success,
                $currency,
                $amount,
                $sender->id,
                $recipient->id,
                'User',
                $meta,
                $clientReference
            );

            (new Txn)->new(
                $amount,
                0,
                $amount,
                'Wallet Transfer',
                'Received from '.$sender->full_name,
                TxnType::Transfer,
                TxnStatus::Success,
                $currency,
                $amount,
                $recipient->id,
                $sender->id,
                'User',
                array_merge($meta, ['sender_id' => $sender->id]),
                'RECV:'.$clientReference
            );

            return $this->formatTransferResult($senderTxn, $recipient, false);
        }, 3);

        // Notification delivery must never turn a successfully committed money
        // movement into an API failure.
        if (! ($result['idempotent_replay'] ?? false)) {
            try {
                $this->sendTransferNotifications(
                    User::findOrFail($senderId),
                    User::findOrFail($recipientId),
                    $amount
                );
            } catch (\Throwable $notificationError) {
                Log::warning('Transfer committed but notification delivery failed.', [
                    'sender_id' => $senderId,
                    'recipient_id' => $recipientId,
                    'error' => $notificationError->getMessage(),
                ]);
            }
        }

        return $result;
    }

    private function validateFeatureAccess(User $sender): void
    {
        $access = $this->transferAccess($sender);

        if (! $access['global_enabled']) {
            throw ValidationException::withMessages(['transfer' => __('Transfers are temporarily disabled globally.')]);
        }

        if (! $access['role_enabled']) {
            throw ValidationException::withMessages([
                'transfer' => __('Transfers are disabled for :type accounts.', [
                    'type' => $access['user_type'],
                ]),
            ]);
        }

        if (! $access['user_enabled']) {
            throw ValidationException::withMessages(['transfer' => __('Transfer is not enabled for your account.')]);
        }

        if ($access['kyc_required'] && ! $access['kyc_verified']) {
            throw ValidationException::withMessages(['transfer' => __('KYC verification is required to send money.')]);
        }
    }

    /**
     * Resolve every switch that controls transfers for a user.
     *
     * The buyer/merchant settings are role-wide switches. The per-user flag is
     * retained as an account-level override so an administrator can still
     * disable one account without disabling the whole role.
     */
    private function transferAccess(User $user): array
    {
        $userType = $user->user_type === 'merchant' ? 'merchant' : 'buyer';
        $roleSetting = $userType === 'merchant'
            ? 'transfer_default_merchant'
            : 'transfer_default_buyer';

        $globalEnabled = setting_enabled('transfer_global_status', 'transfer', true);
        $roleEnabled = setting_enabled($roleSetting, 'transfer', true);
        $userEnabled = value_is_enabled($user->transfer_status);
        $kycRequired = setting_enabled('transfer_require_kyc', 'transfer', false);
        $kycVerified = (bool) $user->kyc;

        $disabledReason = null;
        if (! $globalEnabled) {
            $disabledReason = 'global_disabled';
        } elseif (! $roleEnabled) {
            $disabledReason = $userType.'_disabled';
        } elseif (! $userEnabled) {
            $disabledReason = 'account_disabled';
        } elseif ($kycRequired && ! $kycVerified) {
            $disabledReason = 'kyc_required';
        }

        return [
            'enabled' => $disabledReason === null,
            'global_enabled' => $globalEnabled,
            'role_enabled' => $roleEnabled,
            'user_enabled' => $userEnabled,
            'user_type' => $userType,
            'kyc_required' => $kycRequired,
            'kyc_verified' => $kycVerified,
            'disabled_reason' => $disabledReason,
        ];
    }

    private function validateRecipient(User $sender, ?User $recipient): void
    {
        if (! $recipient) {
            throw ValidationException::withMessages(['recipient_phone' => __('Recipient not found.')]);
        }
        if ((int) $recipient->status !== 1) {
            throw ValidationException::withMessages(['recipient_phone' => __('Recipient account is not active.')]);
        }
        if ((int) $sender->id === (int) $recipient->id) {
            throw ValidationException::withMessages(['recipient_phone' => __('You cannot send money to yourself.')]);
        }
    }

    private function resolveRecipient(string $phone, ?int $recipientId = null): ?User
    {
        if ($recipientId) {
            $recipient = User::find($recipientId);
            if ($recipient && in_array($this->phoneDigits((string) $recipient->phone), array_map([$this, 'phoneDigits'], $this->phoneCandidates($phone)), true)) {
                return $recipient;
            }
        }

        $candidates = $this->phoneCandidates($phone);
        if ($candidates === []) {
            return null;
        }

        // Exact matching only. The old trailing LIKE lookup could resolve an
        // ambiguous user and then fail the actual transfer validation.
        return User::query()->whereIn('phone', $candidates)->first();
    }

    private function phoneCandidates(string $phone): array
    {
        $raw = trim($phone);
        $digits = $this->phoneDigits($raw);
        if ($digits === '') {
            return [];
        }

        $candidates = [];
        $add = static function (string $value) use (&$candidates): void {
            $value = trim($value);
            if ($value !== '' && ! in_array($value, $candidates, true)) {
                $candidates[] = $value;
            }
        };

        if (str_starts_with($raw, '+')) {
            $add('+'.$digits);
            $add($digits);
        } elseif (str_starts_with($digits, '00')) {
            $international = substr($digits, 2);
            $add('+'.$international);
            $add($international);
        } else {
            $add($raw);
            $add($digits);
        }

        $sender = auth()->user();
        $dialCode = $sender?->country ? (string) (getCountryData($sender->country, 'dial_code') ?? '') : '';
        $dialDigits = $this->phoneDigits($dialCode);

        if ($dialDigits !== '') {
            $local = $digits;
            if (str_starts_with($local, $dialDigits)) {
                $local = substr($local, strlen($dialDigits));
            }
            $local = ltrim($local, '0');
            if ($local !== '') {
                $add('+'.$dialDigits.$local);
                $add($dialDigits.$local);
                $add('0'.$local);
                $add($local);
            }
        }

        return array_values($candidates);
    }

    private function phoneDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: '';
    }

    private function formatTransferResult(Transaction $transaction, User $recipient, bool $idempotent): array
    {
        return [
            'success' => true,
            'message' => __('Transfer successful.'),
            'tnx' => $transaction->tnx,
            'client_reference' => $transaction->approval_cause,
            'idempotent_replay' => $idempotent,
            'transaction' => [
                'tnx' => $transaction->tnx,
                'amount' => (float) $transaction->amount,
                'recipient' => $recipient->full_name,
                'recipient_id' => $recipient->id,
                'recipient_phone' => $recipient->phone,
                'status' => TxnStatus::Success->value,
            ],
        ];
    }

    private function sendTransferNotifications(User $sender, User $recipient, float $amount): void
    {
        $this->sendNotify(
            $sender->email,
            'transfer_sent',
            'User',
            [
                '[[amount]]' => amountWithCurrency($amount),
                '[[recipient]]' => $recipient->full_name,
                '[[recipient_phone]]' => $recipient->phone,
                '[[site_title]]' => setting('site_title', 'global'),
            ],
            $sender->phone,
            $sender->id
        );

        $this->sendNotify(
            $recipient->email,
            'transfer_received',
            'User',
            [
                '[[amount]]' => amountWithCurrency($amount),
                '[[sender]]' => $sender->full_name,
                '[[sender_phone]]' => $sender->phone,
                '[[site_title]]' => setting('site_title', 'global'),
            ],
            $recipient->phone,
            $recipient->id
        );
    }
}
