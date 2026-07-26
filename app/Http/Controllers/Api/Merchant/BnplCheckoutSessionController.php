<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Enums\OrderStatus;
use App\Enums\ProviderPlatform;
use App\Http\Controllers\Controller;
use App\Models\BnplCheckoutSession;
use App\Services\MerchantBnplGatewayService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BnplCheckoutSessionController extends Controller
{
    use ApiResponse;

    public function checkoutSession(Request $request, MerchantBnplGatewayService $gatewayService)
    {
        $merchant = $gatewayService->resolveMerchantFromSignedRequest($request, '/bnpl/checkout-session');
        
        if (!$merchant) {
            return $this->unauthorizedResponse($this->merchantUnavailableMessage());
        }

        $isSandbox = $gatewayService->isSandboxRequest($request);

        $payload = $request->json()->all() ?: $request->all();

        $validator = Validator::make($payload, [
            'merchant_public_key' => ['required', 'string'],
            'merchant_order_id' => ['required', 'string', 'max:191'],
            'merchant_reference_id' => ['nullable', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'max:20'],
            'timestamp' => ['required'],
            'request_signature' => ['required', 'string'],
            'customer' => ['nullable', 'array'],
            'items' => ['nullable', 'array'],
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
            'callback_url' => ['nullable', 'url'],
            'webhook_url' => ['nullable', 'url'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ((string) $merchant->public_key !== (string) $payload['merchant_public_key']) {
            return $this->unauthorizedResponse($this->merchantUnavailableMessage());
        }

        if (!$gatewayService->verifyCheckoutPayloadSignature($payload, $merchant)) {
            return $this->unauthorizedResponse($this->merchantUnavailableMessage());
        }

        if (!$this->currencyMatchesSite((string) $payload['currency'])) {
            return $this->validationErrorResponse($this->siteCurrencyMessage());
        }

        $provider = $merchant->provider;
        $sessionPlatform = (string) data_get($payload, 'metadata.platform', $provider?->platform ?: ProviderPlatform::WORDPRESS_WOOCOMMERCE->value);

        $session = BnplCheckoutSession::query()->create([
            'token' => 'bcs_' . Str::lower(Str::random(72)),
            'merchant_id' => $merchant->id,
            'merchant_public_key' => $merchant->public_key,
            'merchant_order_id' => (string) $payload['merchant_order_id'],
            'merchant_reference_id' => (string) ($payload['merchant_reference_id'] ?? ''),
            'platform' => $sessionPlatform,
            'is_sandbox' => $isSandbox,
            'status' => 'pending',
            'amount' => round((float) $payload['amount'], 2),
            'currency' => (string) $payload['currency'],
            'customer' => (array) ($payload['customer'] ?? []),
            'items' => array_values((array) ($payload['items'] ?? [])),
            'payload' => $payload,
            'success_url' => (string) ($payload['success_url'] ?? ''),
            'callback_url' => (string) ($payload['callback_url'] ?? ''),
            'webhook_url' => (string) ($payload['webhook_url'] ?? ''),
            'cancel_url' => (string) ($payload['cancel_url'] ?? ''),
        ]);

        $redirectUrl = route('bnpl.auth', ['session' => $session->token]);

        return response()->json([
            'status' => 'success',
            'message' => __('BNPL checkout session created successfully.'),
            'data' => [
                'session_id' => $session->token,
                'redirect_url' => $redirectUrl,
                'checkout_url' => $redirectUrl,
                'sandbox_mode' => $session->is_sandbox,
            ],
        ], 201);
    }

    public function syncOrderStatus(Request $request, MerchantBnplGatewayService $gatewayService)
    {
        $merchant = $gatewayService->resolveMerchantFromSignedRequest($request, '/bnpl/order-status');
        
        if (!$merchant) {
            return $this->unauthorizedResponse($this->merchantUnavailableMessage());
        }

        $payload = $request->json()->all() ?: $request->all();

        $validator = Validator::make($payload, [
            'session_id' => ['nullable', 'string'],
            'merchant_order_id' => ['required', 'string', 'max:191'],
            'woocommerce_status' => ['required', 'string', 'max:50'],
            'previous_status' => ['nullable', 'string', 'max:50'],
            'order_key' => ['nullable', 'string', 'max:191'],
            'total_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:20'],
            'updated_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $session = BnplCheckoutSession::query()
            ->where('merchant_id', $merchant->id)
            ->when(
                filled($payload['session_id'] ?? null),
                fn($query) => $query->where('token', (string) $payload['session_id']),
                fn($query) => $query->where('merchant_order_id', (string) $payload['merchant_order_id'])->latest('id')
            )
            ->latest('id')
            ->first();

        if (!$session) {
            return $this->notFoundResponse(__('BNPL checkout session not found.'));
        }

        $wooStatus = Str::lower((string) $payload['woocommerce_status']);
        $session->merchant_status = $wooStatus;

        if ($session->is_sandbox) {
            $session->status = match (true) {
                in_array($wooStatus, ['cancelled', 'canceled'], true) => 'cancelled',
                $wooStatus === 'failed' => 'failed',
                $wooStatus === 'refunded' => 'refunded',
                $wooStatus === 'completed' => 'completed',
                $wooStatus === 'processing' => 'confirmed',
                default => $session->status,
            };
            $session->save();

            return response()->json([
                'status' => 'success',
                'message' => __('Sandbox merchant order status stored successfully.'),
                'data' => [
                    'session_id' => $session->token,
                    'status' => $session->status,
                    'merchant_status' => $session->merchant_status,
                    'sandbox_mode' => true,
                ],
            ]);
        }

        if (!$session->order_id) {
            $session->save();

            return response()->json([
                'status' => 'success',
                'message' => __('Merchant order status stored successfully.'),
                'data' => [
                    'session_id' => $session->token,
                    'status' => $session->status,
                    'merchant_status' => $session->merchant_status,
                    'sandbox_mode' => false,
                ],
            ]);
        }

        $order = $session->order()->with(['items', 'transaction', 'bnplItemLoans.installments'])->first();
        if (!$order) {
            $session->save();

            return response()->json([
                'status' => 'success',
                'message' => __('Merchant order status stored successfully.'),
                'data' => [
                    'session_id' => $session->token,
                    'status' => $session->status,
                    'merchant_status' => $session->merchant_status,
                    'sandbox_mode' => false,
                ],
            ]);
        }

        $service = orderService();

        if (in_array($wooStatus, ['cancelled', 'canceled'], true) && !in_array($order->status, [OrderStatus::Cancelled->value, OrderStatus::Refunded->value], true)) {
            $service->setOrderCancelled($order, false);
            $session->status = 'cancelled';
        } elseif ($wooStatus === 'failed' && $order->status !== OrderStatus::Failed->value) {
            $service->setOrderFailed($order, false);
            $session->status = 'failed';
        } elseif ($wooStatus === 'refunded' && $order->status !== OrderStatus::Refunded->value) {
            $service->setOrderRefunded($order, false);
            $session->status = 'refunded';
        } elseif ($wooStatus === 'completed' && !in_array($order->status, [OrderStatus::Cancelled->value, OrderStatus::Failed->value, OrderStatus::Refunded->value], true)) {
            $order->update([
                'status' => OrderStatus::Delivered->value,
                'delivered_at' => $order->delivered_at ?? now(),
            ]);
            $order->items()->update(['status' => OrderStatus::Delivered->value]);
            $session->status = 'completed';
        } elseif ($wooStatus === 'processing' && !in_array($order->status, [OrderStatus::Cancelled->value, OrderStatus::Failed->value, OrderStatus::Refunded->value], true)) {
            $order->update(['status' => OrderStatus::Success->value]);
            $order->items()->update(['status' => OrderStatus::Success->value]);
            $session->status = 'confirmed';
        } elseif (in_array($wooStatus, ['pending', 'on-hold'], true) && !in_array($order->status, [OrderStatus::Cancelled->value, OrderStatus::Failed->value, OrderStatus::Refunded->value], true)) {
            $order->update(['status' => OrderStatus::Pending->value]);
            $order->items()->update(['status' => OrderStatus::Pending->value]);
            $session->status = 'pending';
        }

        $session->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Merchant order status synchronized successfully.'),
            'data' => [
                'session_id' => $session->token,
                'status' => $session->status,
                'merchant_status' => $session->merchant_status,
                'order_status' => $order->fresh()->status,
                'sandbox_mode' => false,
            ],
        ]);
    }

    private function currencyMatchesSite(string $currency): bool
    {
        $siteCurrency = strtoupper(trim((string) setting('site_currency', 'global')));
        $orderCurrency = strtoupper(trim($currency));

        if ($siteCurrency === '' || $orderCurrency === '') {
            return false;
        }

        return $siteCurrency === $orderCurrency;
    }

    private function merchantUnavailableMessage(): string
    {
        return __('BNPL is temporarily unavailable for this store. Please choose another payment method or contact the store.');
    }

    private function siteCurrencyMessage(): string
    {
        return __('BNPL is only available for :currency orders on this store.', [
            'currency' => (string) setting('site_currency', 'global'),
        ]);
    }
}
