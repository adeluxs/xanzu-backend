<?php

namespace App\Services\Payments;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Exceptions\PaymentGatewayException;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class RayplusmoneyService
{
    public const GATEWAY_CODE = 'rayplusmoney';

    public function createPayin(Transaction $transaction): array
    {
        $transaction->loadMissing('user');
        try {
            $credentials = $this->credentials();
        } catch (\Throwable $e) {
            throw new PaymentGatewayException(
                __('RayPlusMoney is not configured correctly. Please contact support.'),
                self::GATEWAY_CODE,
                'PAYMENT_GATEWAY_CONFIGURATION_ERROR',
                retryable: false,
                diagnosticContext: [
                    'configuration_error' => $this->sanitizeDiagnosticText($e->getMessage()),
                    'exception' => get_class($e),
                ],
                previous: $e,
            );
        }

        $currency = strtoupper((string) $transaction->pay_currency);
        if ($currency !== 'XOF') {
            throw new PaymentGatewayException(
                __('RayPlusMoney pay-ins require the deposit method currency to be XOF.'),
                self::GATEWAY_CODE,
                'PAYMENT_GATEWAY_CONFIGURATION_ERROR',
                diagnosticContext: ['pay_currency' => $currency],
            );
        }

        $amount = (int) round((float) $transaction->pay_amount);
        if ($amount < 1) {
            throw new PaymentGatewayException(
                __('The RayPlusMoney payable amount must be at least 1 XOF.'),
                self::GATEWAY_CODE,
                'PAYMENT_GATEWAY_CONFIGURATION_ERROR',
                diagnosticContext: [
                    'pay_amount' => $amount,
                    'pay_currency' => $currency,
                ],
            );
        }

        [$firstName, $lastName] = $this->splitName((string) $transaction->user->full_name);
        $encryptedReference = encrypt($transaction->tnx);

        $payload = [
            'commande' => [
                'invoice' => [
                    'items' => [[
                        'name' => setting('site_title', 'global').' Wallet Top-up',
                        'description' => 'Wallet top-up',
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'total_price' => $amount,
                    ]],
                    'total_amount' => $amount,
                    'devise' => 'XOF',
                    'description' => 'Wallet top-up for '.$transaction->user->full_name,
                    'customer' => $this->normalizeCustomerPhone((string) $transaction->user->phone),
                    'customer_firstname' => $firstName,
                    'customer_lastname' => $lastName,
                    'customer_email' => (string) $transaction->user->email,
                    // RayPlus' sample payload uses externalid (without an underscore).
                    'externalid' => $transaction->tnx,
                ],
                'store' => [
                    'name' => setting('site_title', 'global'),
                    'website_url' => url('/'),
                ],
                'actions' => [
                    'cancel_url' => route('status.rayplusmoney.cancel', ['reftrn' => $encryptedReference]),
                    'returnurl' => route('status.rayplusmoney.success', ['reftrn' => $encryptedReference]),
                    'callback_url' => route('ipn.rayplusmoney', ['reftrn' => $encryptedReference]),
                    'callbackurl__method' => 'post_json',
                ],
                'custom_data' => [
                    'transaction_id' => $transaction->tnx,
                    'uid' => (string) Str::uuid(),
                    'ref' => $transaction->tnx,
                    'amount' => $amount,
                ],
            ],
        ];

        $endpointPath = 'redirect/checkout-invoice/create';
        $endpoint = $this->endpoint($endpointPath, $credentials);

        try {
            $response = $this->client($credentials)->post($endpoint, $payload);
        } catch (\Throwable $e) {
            throw new PaymentGatewayException(
                __('Unable to contact RayPlusMoney. Please try again.'),
                self::GATEWAY_CODE,
                'PAYMENT_GATEWAY_UNAVAILABLE',
                endpoint: $endpointPath,
                retryable: true,
                diagnosticContext: ['transport_exception' => get_class($e)],
                previous: $e,
            );
        }

        $httpStatus = $response->status();
        $data = $response->json();
        if (! is_array($data)) {
            throw new PaymentGatewayException(
                __('RayPlusMoney returned an invalid response. Please try again.'),
                self::GATEWAY_CODE,
                'PAYMENT_GATEWAY_INVALID_RESPONSE',
                providerHttpStatus: $httpStatus,
                endpoint: $endpointPath,
                retryable: $httpStatus >= 500,
                diagnosticContext: [
                    'response_content_type' => $response->header('Content-Type'),
                    'response_body_length' => strlen($response->body()),
                ],
            );
        }

        $providerCode = $this->providerCode($data);
        $providerMessage = $this->sanitizeDiagnosticText(
            $this->gatewayErrorMessage($data, 'RayPlusMoney rejected the payment request.')
        );
        $providerRequestId = $this->providerRequestId($data);

        if ($httpStatus < 200 || $httpStatus >= 300 || $providerCode !== '00') {
            $displayMessage = __('RayPlusMoney rejected the payment request: :reason', [
                'reason' => $this->messageWithCode($providerMessage, $providerCode),
            ]);
            $exception = new PaymentGatewayException(
                $displayMessage,
                self::GATEWAY_CODE,
                'PAYMENT_GATEWAY_REJECTED',
                providerHttpStatus: $httpStatus,
                providerCode: $providerCode !== '' ? $providerCode : null,
                providerMessage: $providerMessage,
                providerRequestId: $providerRequestId,
                endpoint: $endpointPath,
                retryable: $httpStatus >= 500,
                diagnosticContext: [
                    'pay_amount' => $amount,
                    'pay_currency' => $currency,
                    'response_fields' => array_slice(array_keys($data), 0, 30),
                ],
            );

            Log::warning('RAYPLUS_PAYIN_REJECTED', [
                'tnx' => $transaction->tnx,
                ...$exception->logContext(),
            ]);

            throw $exception;
        }

        $token = trim((string) ($data['token'] ?? ''));
        $redirectUrl = trim((string) ($data['response_text'] ?? ''));
        if ($token === '') {
            throw new PaymentGatewayException(
                __('RayPlusMoney accepted the request without returning a transaction token.'),
                self::GATEWAY_CODE,
                'PAYMENT_GATEWAY_INVALID_RESPONSE',
                providerHttpStatus: $httpStatus,
                providerCode: $providerCode,
                providerRequestId: $providerRequestId,
                endpoint: $endpointPath,
                retryable: true,
                diagnosticContext: ['response_fields' => array_slice(array_keys($data), 0, 30)],
            );
        }
        if (! filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            throw new PaymentGatewayException(
                __('RayPlusMoney did not return a valid hosted payment URL.'),
                self::GATEWAY_CODE,
                'PAYMENT_GATEWAY_INVALID_RESPONSE',
                providerHttpStatus: $httpStatus,
                providerCode: $providerCode,
                providerRequestId: $providerRequestId,
                endpoint: $endpointPath,
                retryable: true,
                diagnosticContext: ['response_fields' => array_slice(array_keys($data), 0, 30)],
            );
        }

        $transaction->update(['approval_cause' => $token]);

        return [
            'is_redirect' => true,
            'redirect_url' => $redirectUrl,
            'token' => $token,
            'response_code' => '00',
            'payment_status' => TxnStatus::Pending->value,
            'provider' => self::GATEWAY_CODE,
        ];
    }

    public function createPayout(Transaction $transaction): array
    {
        $transaction->loadMissing('user');
        try {
            $credentials = $this->credentials();
        } catch (\Throwable $e) {
            // No provider request was made, so it is safe to return the reserved
            // wallet funds immediately.
            $this->failUndispatchedWithdrawal($transaction);
            throw $e;
        }

        if (strtoupper((string) $transaction->pay_currency) !== 'XOF') {
            $this->failUndispatchedWithdrawal($transaction);
            throw new RuntimeException(__('RayPlusMoney payouts require the withdraw method currency to be XOF.'));
        }

        $meta = json_decode((string) $transaction->manual_field_data, true) ?: [];
        $networkValue = $this->credentialValue($meta, ['network', 'network_id', 'network id', 'operator', 'operator_id']);
        $network = (int) ($networkValue !== null && $networkValue !== '' ? $networkValue : ($credentials['payout_network'] ?? 0));
        $customerValue = $this->credentialValue($meta, ['customer', 'phone', 'phone_number', 'phone number', 'mobile', 'mobile_number', 'msisdn']);
        $customer = $this->normalizeCustomerPhone((string) ($customerValue ?: $transaction->user->phone));
        $amount = (int) round((float) $transaction->pay_amount);

        if ($amount < 1) {
            $this->failUndispatchedWithdrawal($transaction);
            throw new RuntimeException(__('The RayPlusMoney payout amount must be at least 1 XOF.'));
        }
        if ($network <= 0) {
            $this->failUndispatchedWithdrawal($transaction);
            throw new RuntimeException(__('RayPlusMoney payout requires a valid Mobile Money network ID.'));
        }
        if ($customer === '') {
            $this->failUndispatchedWithdrawal($transaction);
            throw new RuntimeException(__('RayPlusMoney payout requires a valid customer mobile number.'));
        }

        $payload = [
            'commande' => [
                'amount' => $amount,
                'network' => $network,
                'customer' => $customer,
                'custom_data' => [
                    'transactionid' => $transaction->tnx,
                    'ref' => $transaction->tnx,
                ],
                'callback_url' => route('ipn.rayplusmoney', ['reftrn' => encrypt($transaction->tnx)]),
                'callbackurl__method' => 'post_json',
                'top_up_wallet' => 0,
                'external_id' => $transaction->tnx,
            ],
        ];

        $response = $this->client($credentials)->post(
            $this->endpoint('straight/payout', $credentials),
            $payload
        );
        $data = $this->decodeResponse($response->status(), $response->json(), $response->body());

        if ((string) ($data['response_code'] ?? '') !== '00' || trim((string) ($data['token'] ?? '')) === '') {
            $this->failUndispatchedWithdrawal($transaction);
            throw new RuntimeException($this->gatewayErrorMessage($data, 'RayPlusMoney rejected the payout request.'));
        }

        $token = trim((string) $data['token']);
        $transaction->update([
            'approval_cause' => $token,
            'status' => TxnStatus::Pending,
        ]);

        return [
            'accepted' => true,
            'token' => $token,
            'payment_status' => TxnStatus::Pending->value,
        ];
    }

    public function reconcile(Transaction $transaction): array
    {
        $transaction->refresh();

        if ($transaction->status === TxnStatus::Success) {
            $this->finalizeRelatedPayment($transaction);
            return $this->result($transaction, 'completed');
        }

        $token = trim((string) $transaction->approval_cause);
        if ($token === '' || $token === 'none') {
            return $this->result($transaction, 'pending', __('RayPlusMoney transaction token is not available yet.'));
        }

        $credentials = $this->credentials();
        $isWithdraw = in_array($transaction->type, [TxnType::Withdraw, TxnType::WithdrawAuto], true);
        $endpoint = $isWithdraw ? 'withdrawal/confirm' : 'redirect/checkout-invoice/confirm';
        $query = $isWithdraw ? ['withdrawalToken' => $token] : ['invoiceToken' => $token];

        try {
            $response = $this->client($credentials)->get($this->endpoint($endpoint, $credentials), $query);
            $data = $this->decodeResponse($response->status(), $response->json(), $response->body());
        } catch (\Throwable $e) {
            Log::warning('RayPlusMoney status verification failed.', [
                'tnx' => $transaction->tnx,
                'error' => $e->getMessage(),
            ]);
            return $this->result($transaction, 'pending', __('Payment verification is temporarily unavailable.'));
        }

        if ((string) ($data['response_code'] ?? '') !== '00') {
            return $this->result($transaction, 'pending', $this->gatewayErrorMessage($data, 'Payment verification is pending.'));
        }

        $providerStatus = strtolower(trim((string) ($data['status'] ?? 'pending')));
        if (! in_array($providerStatus, ['pending', 'completed', 'notcompleted'], true)) {
            $providerStatus = 'pending';
        }

        $locked = DB::transaction(function () use ($transaction, $providerStatus, $isWithdraw) {
            /** @var Transaction|null $locked */
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->first();
            if (! $locked) {
                return null;
            }

            /** @var User|null $user */
            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();

            if ($providerStatus === 'completed') {
                if ($locked->status !== TxnStatus::Success) {
                    if (in_array($locked->type, [TxnType::Deposit, TxnType::ManualDeposit], true)) {
                        // Credit the requested wallet amount (not the gateway charge or
                        // converted pay amount) exactly once.
                        if ($user) {
                            $user->balance = round((float) $user->balance + (float) $locked->amount, 2);
                            $user->save();
                        }
                    }
                    $locked->status = TxnStatus::Success;
                    $locked->save();
                }
            } elseif ($providerStatus === 'notcompleted') {
                if ($locked->status === TxnStatus::Pending) {
                    if ($isWithdraw && $user) {
                        $user->balance = round((float) $user->balance + (float) $locked->final_amount, 2);
                        $user->save();
                    }
                    $locked->status = TxnStatus::Failed;
                    $locked->save();
                }
            }

            return $locked->fresh();
        }, 3);

        if (! $locked) {
            throw new RuntimeException(__('Transaction no longer exists.'));
        }

        if ($providerStatus === 'completed' && $locked->status === TxnStatus::Success) {
            $this->finalizeRelatedPayment($locked);
        }

        return $this->result($locked, $providerStatus);
    }

    public function findCallbackTransaction(array $callback, ?string $encryptedReference = null): ?Transaction
    {
        // Prefer the signed merchant reference embedded in our callback URL.
        // It is deterministic and indexed through the transaction reference,
        // while RayPlus tokens are long text values and may not be indexed.
        if ($encryptedReference) {
            try {
                $reference = decrypt($encryptedReference);
                $byReference = Transaction::query()->where('tnx', $reference)->first();
                if ($byReference) {
                    return $byReference;
                }
            } catch (\Throwable) {
                // Continue with provider payload correlation below.
            }
        }

        $customData = $callback['customdata'] ?? $callback['custom_data'] ?? [];
        if (is_string($customData)) {
            $customData = json_decode($customData, true) ?: [];
        }
        $merchantReference = trim((string) ($customData['transaction_id'] ?? $customData['transactionid'] ?? $customData['ref'] ?? ''));
        if ($merchantReference !== '') {
            $byReference = Transaction::query()->where('tnx', $merchantReference)->first();
            if ($byReference) {
                return $byReference;
            }
        }

        $token = trim((string) ($callback['token'] ?? ''));
        if ($token !== '') {
            $byToken = Transaction::query()->where('approval_cause', $token)->first();
            if ($byToken) {
                return $byToken;
            }
        }

        return null;
    }

    public function markCancelled(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === TxnStatus::Pending) {
                $locked->status = TxnStatus::Cancelled;
                $locked->save();
            }
            return $locked->fresh();
        });
    }

    private function finalizeRelatedPayment(Transaction $transaction): void
    {
        try {
            if ($transaction->type === TxnType::BnplInstallment) {
                orderService()->markBnplInstallmentTransactionPaid($transaction->fresh());
                return;
            }

            if (! $transaction->order_id) {
                return;
            }

            $order = $transaction->order()->first();
            if (! $order || $order->payment_status === TxnStatus::Success->value) {
                return;
            }

            orderService()->orderPaymentSuccess($order, false);
        } catch (\Throwable $e) {
            // Keep the provider-confirmed transaction successful, but make the
            // downstream order issue visible and retryable on the next status
            // check/callback instead of silently losing it.
            Log::error('RayPlusMoney payment was confirmed but related order finalization failed.', [
                'tnx' => $transaction->tnx,
                'order_id' => $transaction->order_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function failUndispatchedWithdrawal(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== TxnStatus::Pending) {
                return;
            }
            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();
            if ($user) {
                $user->balance = round((float) $user->balance + (float) $locked->final_amount, 2);
                $user->save();
            }
            $locked->status = TxnStatus::Failed;
            $locked->save();
        });
    }

    private function credentials(): array
    {
        $gateway = Gateway::query()->where('gateway_code', self::GATEWAY_CODE)->first();
        $stored = $gateway ? (json_decode((string) $gateway->credentials, true) ?: []) : [];

        $credentials = [
            'base_url' => trim((string) ($stored['base_url'] ?? config('rayplusmoney.base_url'))),
            'api_key' => trim((string) ($stored['api_key'] ?? config('rayplusmoney.api_key'))),
            'api_token' => trim((string) ($stored['api_token'] ?? config('rayplusmoney.api_token'))),
            'payout_network' => $stored['payout_network'] ?? config('rayplusmoney.payout_network'),
        ];

        if ($credentials['base_url'] === '' || $credentials['api_key'] === '' || $credentials['api_token'] === '') {
            throw new RuntimeException(__('RayPlusMoney credentials are incomplete. Configure Base URL, API Key and API Token.'));
        }

        return $credentials;
    }

    private function endpoint(string $path, array $credentials): string
    {
        $base = rtrim((string) $credentials['base_url'], '/');
        // Accept either https://host or https://host/pay/v01 and collapse the
        // old duplicated /pay/v01/pay/v01 configuration safely.
        $base = preg_replace('#(?:/pay/v01)+$#i', '/pay/v01', $base) ?: $base;
        if (! preg_match('#/pay/v01$#i', $base)) {
            $base .= '/pay/v01';
        }

        return $base.'/'.ltrim($path, '/');
    }

    private function client(array $credentials): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => 'Bearer '.$credentials['api_token'],
                'Apikey' => $credentials['api_key'],
            ])
            ->connectTimeout((int) config('rayplusmoney.connect_timeout', 8))
            ->timeout((int) config('rayplusmoney.timeout', 20))
            ->retry(2, 300, throw: false);
    }

    private function decodeResponse(int $httpStatus, mixed $json, string $body): array
    {
        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw new RuntimeException(__('RayPlusMoney returned HTTP :status.', ['status' => $httpStatus]));
        }
        if (! is_array($json)) {
            Log::warning('RayPlusMoney returned a non-JSON response.', ['body' => Str::limit($body, 500)]);
            throw new RuntimeException(__('RayPlusMoney returned an invalid response.'));
        }
        return $json;
    }

    private function result(Transaction $transaction, string $providerStatus, ?string $message = null): array
    {
        $status = $transaction->status instanceof TxnStatus ? $transaction->status->value : (string) $transaction->status;
        return [
            'tnx' => $transaction->tnx,
            'provider' => self::GATEWAY_CODE,
            'status' => $status,
            'payment_status' => $providerStatus,
            'completed' => $status === TxnStatus::Success->value,
            'failed' => $status === TxnStatus::Failed->value,
            'pending' => in_array($status, [TxnStatus::Pending->value, TxnStatus::Cancelled->value], true) && $providerStatus === 'pending',
            'message' => $message,
        ];
    }

    private function gatewayErrorMessage(array $data, string $fallback): string
    {
        foreach (['description', 'response_text', 'message', 'error.message', 'error', 'errors.message', 'data.message'] as $key) {
            $rawValue = Arr::get($data, $key, '');
            if (! is_scalar($rawValue)) {
                continue;
            }
            $value = trim((string) $rawValue);
            if ($value !== '' && ! filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }
        }
        return __($fallback);
    }

    private function providerCode(array $data): string
    {
        return trim((string) ($data['response_code'] ?? $data['responseCode'] ?? $data['code'] ?? $data['status_code'] ?? ''));
    }

    private function providerRequestId(array $data): ?string
    {
        foreach (['request_id', 'requestId', 'reference', 'transaction_id', 'external_id', 'data.request_id', 'data.reference'] as $key) {
            $rawValue = Arr::get($data, $key, '');
            if (! is_scalar($rawValue)) {
                continue;
            }
            $value = trim((string) $rawValue);
            if ($value !== '') {
                return Str::limit($value, 160, '');
            }
        }

        return null;
    }

    private function messageWithCode(string $message, string $code): string
    {
        $message = Str::limit(trim($message), 300, '');
        if ($code === '' || str_contains(strtolower($message), strtolower($code))) {
            return $message;
        }

        return $message.' ('.$code.')';
    }

    private function sanitizeDiagnosticText(string $message): string
    {
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message) ?? $message;

        return Str::limit(trim($message), 300, '');
    }


    /**
     * Withdraw-account credentials are stored as keyed field definitions
     * (name/type/validation/value), while older records may be flat key/value
     * arrays. Resolve both formats without tying RayPlusMoney to one UI shape.
     */
    private function credentialValue(array $meta, array $aliases): mixed
    {
        $normalize = static fn (string $value): string => trim(strtolower(preg_replace('/[^a-z0-9]+/i', '_', $value) ?? $value), '_');
        $wanted = array_map($normalize, $aliases);

        foreach ($meta as $key => $value) {
            $keyName = $normalize((string) $key);
            if (! is_array($value) && in_array($keyName, $wanted, true)) {
                return $value;
            }

            if (! is_array($value)) {
                continue;
            }

            $fieldName = $normalize((string) ($value['name'] ?? $key));
            if (in_array($fieldName, $wanted, true) || in_array($keyName, $wanted, true)) {
                return $value['value'] ?? null;
            }
        }

        return null;
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];
        return [$parts[0] ?? 'Customer', $parts[1] ?? ''];
    }

    private function normalizeCustomerPhone(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone) ?: $phone;
    }
}
