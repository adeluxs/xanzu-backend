<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantBnplGatewayService
{
    public function resolveMerchantFromSignedRequest(Request $request, string $endpoint, string $method = 'POST'): ?User
    {
        $publicKey = trim((string) $request->header('X-Qunzo-Public-Key'));
        $timestamp = trim((string) $request->header('X-Qunzo-Timestamp'));
        $nonce = trim((string) $request->header('X-Qunzo-Nonce'));
        $signature = trim((string) $request->header('X-Qunzo-Signature'));

        if ($publicKey === '' || $timestamp === '' || $nonce === '' || $signature === '') {
            return null;
        }

        $merchant = User::query()
            ->where('public_key', $publicKey)
            ->where('user_type', 'merchant')
            ->first();

        if (!$merchant || blank($merchant->secret_key)) {
            return null;
        }

        if (!$this->isRecentTimestamp($timestamp)) {
            return null;
        }

        $rawBody = $request->getContent();
        $expected = hash_hmac('sha256', implode('|', [
            strtoupper($method),
            $endpoint,
            $timestamp,
            $nonce,
            $rawBody,
        ]), (string) $merchant->secret_key);

        return hash_equals($expected, $signature) ? $merchant : null;
    }

    public function verifyCheckoutPayloadSignature(array $payload, User $merchant): bool
    {
        $signature = trim((string) data_get($payload, 'request_signature'));
        if ($signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', implode('|', [
            (string) data_get($payload, 'merchant_public_key'),
            (string) data_get($payload, 'merchant_order_id'),
            $this->formatAmount(data_get($payload, 'amount', 0)),
            (string) data_get($payload, 'currency'),
            (string) data_get($payload, 'timestamp'),
        ]), (string) $merchant->secret_key);

        return hash_equals($expected, $signature);
    }

    public function callbackSecret(User $merchant): string
    {
        return (string) ($merchant->webhook_secret ?: $merchant->secret_key ?: config('app.key'));
    }

    public function isSandboxRequest(Request $request): bool
    {
        return Str::contains('/' . ltrim($request->path(), '/'), '/api/merchant/sandbox/');
    }

    public function makeReturnSignature(array $payload, User $merchant): string
    {
        ksort($payload);
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $encodedPayload ?: '', $this->callbackSecret($merchant));
    }

    public function makeWebhookSignature(string $status, string $transactionId, string $totalAmount, User $merchant): string
    {
        return hash_hmac('sha256', implode('|', [
            $status,
            $transactionId,
            $totalAmount,
        ]), $this->callbackSecret($merchant));
    }

    public function formatAmount(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function isRecentTimestamp(string $timestamp): bool
    {
        try {
            $parsed = Carbon::parse($timestamp);
        } catch (\Throwable) {
            return false;
        }

        return abs($parsed->diffInSeconds(now(), false)) <= 300;
    }
}
