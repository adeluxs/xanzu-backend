<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\OrderStatus;
use App\Enums\TxnStatus;
use App\Http\Controllers\Controller;
use App\Models\BnplCheckoutSession;
use App\Models\Order;
use App\Models\CreditLimitSplit;
use App\Models\User;
use App\Services\BnplScheduleService;
use App\Services\MerchantBnplGatewayService;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BnplCheckoutController extends Controller
{
    public function auth(Request $request)
    {
        $sessionToken = trim((string) $request->query('session', $request->input('session')));
        if ($sessionToken !== '') {
            $checkoutSession = BnplCheckoutSession::query()->where('token', $sessionToken)->first();
            if (!$checkoutSession) {
                abort(422, $this->checkoutStartFailedMessage());
            }

            session([
                'bnpl.checkout_session_token' => $checkoutSession->token,
                'bnpl.wp_payload' => $checkoutSession->payload,
            ]);
        }

        $incomingPayload = $this->extractPayload($request);
        if (!empty($incomingPayload)) {
            session(['bnpl.wp_payload' => $incomingPayload]);
        }

        if (!session()->has('bnpl.wp_payload')) {
            abort(422, $this->checkoutStartFailedMessage());
        }

        if (!auth()->check()) {
            $intended = route('bnpl.process');
            if (session()->has('bnpl.checkout_session_token')) {
                $intended = route('bnpl.auth', ['session' => session('bnpl.checkout_session_token')]);
            }

            session(['url.intended' => $intended]);

            return redirect()->route('login');
        }

        return redirect()->route('bnpl.process');
    }

    public function process(Request $request, BnplScheduleService $scheduleService)
    {
        $checkoutSession = $this->resolveCheckoutSession();
        $payload = $checkoutSession?->payload ?: session('bnpl.wp_payload');
        if (!$payload) {
            return redirect()->route('bnpl.auth');
        }

        $payload['items'] = $this->normalizeItems((array) data_get($payload, 'items', []));

        $amount = round((float) data_get($payload, 'amount', 0), 2);
        if ($amount <= 0) {
            abort(422, $this->invalidAmountMessage());
        }

        if (!$this->currencyMatchesSite((string) data_get($payload, 'currency'))) {
            abort(422, $this->siteCurrencyMessage());
        }

        $takeInitialInstallment = (bool) setting('bnpl_take_initial_installment', 'permission');

        $splitPreviews = CreditLimitSplit::query()
            ->active()
            ->latest()
            ->get()
            ->map(function (CreditLimitSplit $split) use ($scheduleService, $amount, $takeInitialInstallment) {
                $preview = $scheduleService->buildSchedulePreview($split, $amount, $takeInitialInstallment);

                return [
                    'split_id' => $split->id,
                    'split_count' => (int) $preview['split_count'],
                    'initial_paid_amount' => (float) $preview['initial_paid_amount'],
                    'final_amount_to_pay' => (float) $preview['final_amount_to_pay'],
                    'total_fees' => (float) $preview['total_fees'],
                    'total_payable' => (float) $preview['total_payable'],
                    'installments' => collect($preview['installments'])->map(function (array $item) {
                        return [
                            'installment_no' => (int) $item['installment_no'],
                            'is_upfront' => (bool) data_get($item, 'is_upfront', false),
                            'status' => (string) data_get($item, 'status', 'pending'),
                            'principal_amount' => round((float) data_get($item, 'principal_amount', 0), 2),
                            'interest_amount' => round((float) data_get($item, 'interest_amount', 0), 2),
                            'total_due_amount' => round((float) data_get($item, 'total_due_amount', 0), 2),
                            'due_at' => data_get($item, 'due_at')?->format('Y-m-d H:i:s'),
                            'display_due_date' => data_get($item, 'due_at')?->format('d M Y'),
                        ];
                    })->values()->all(),
                ];
            })
            ->values();

        if ($splitPreviews->isEmpty()) {
            abort(422, $this->bnplUnavailableMessage());
        }

        return view('frontend::bnpl.process', [
            'payload' => $payload,
            'checkoutSession' => $checkoutSession,
            'splitPreviews' => $splitPreviews,
            'currency' => data_get($payload, 'currency', setting('site_currency', 'global')),
            'amount' => $amount,
            'userBalance' => (float) auth()->user()->balance,
        ]);
    }

    public function confirm(Request $request, BnplScheduleService $scheduleService, OrderService $orderService, MerchantBnplGatewayService $gatewayService)
    {
        $request->validate([
            'split_id' => ['required', 'integer'],
        ]);

        $checkoutSession = $this->resolveCheckoutSession();
        $payload = $checkoutSession?->payload ?: session('bnpl.wp_payload');
        if (!$payload) {
            return redirect()->route('bnpl.auth');
        }

        $amount = round((float) data_get($payload, 'amount', 0), 2);
        if ($amount <= 0) {
            abort(422, $this->invalidAmountMessage());
        }

        if (!$this->currencyMatchesSite((string) data_get($payload, 'currency'))) {
            return back()->withErrors([
                'payment' => $this->siteCurrencyMessage(),
            ])->withInput();
        }

        $merchant = $this->resolveMerchant($payload);
        if (!$merchant) {
            abort(422, $this->merchantUnavailableMessage());
        }

        $split = CreditLimitSplit::query()->active()->whereKey($request->integer('split_id'))->first();
        if (!$split) {
            return back()->withErrors(['split_id' => $this->splitUnavailableMessage()])->withInput();
        }

        $takeInitialInstallment = (bool) setting('bnpl_take_initial_installment', 'permission');
        $preview = $scheduleService->buildSchedulePreview($split, $amount, $takeInitialInstallment);
        $user = auth()->user();
        $order = null;

        if ($checkoutSession) {
            $state = DB::transaction(function () use ($checkoutSession) {
                $lockedSession = BnplCheckoutSession::query()
                    ->whereKey($checkoutSession->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedSession) {
                    throw new Exception('BNPL checkout session not found.');
                }

                if (in_array($lockedSession->status, ['confirmed', 'completed'], true)) {
                    return ['already_confirmed' => true, 'session' => $lockedSession];
                }

                if ($lockedSession->status === 'processing') {
                    throw new Exception($this->checkoutProcessingMessage());
                }

                $lockedSession->status = 'processing';
                $lockedSession->save();

                return ['already_confirmed' => false, 'session' => $lockedSession];
            });

            $checkoutSession = $state['session'];
            if ($state['already_confirmed']) {
                if ($checkoutSession->is_sandbox) {
                    $successUrl = data_get($payload, 'success_url');
                    $redirectUrl = $successUrl
                        ? $this->buildWooReturnUrl($successUrl, $payload, $checkoutSession, $merchant, 'approved', $gatewayService)
                        : route('home');

                    return redirect()->to($redirectUrl)->with('status', 'Sandbox BNPL payment confirmed successfully.');
                }

                $existingOrder = Order::query()->whereKey($checkoutSession->order_id)->first();
                if ($existingOrder) {
                    $successUrl = data_get($payload, 'success_url');
                    $redirectUrl = $successUrl
                        ? $this->buildWooReturnUrl($successUrl, $payload, $checkoutSession, $merchant, 'approved', $gatewayService)
                        : route('home');

                    return redirect()->to($redirectUrl)->with('status', 'BNPL payment confirmed successfully.');
                }
            }
        }

        if ($checkoutSession?->is_sandbox) {
            return $this->confirmSandboxSession($payload, $checkoutSession, $merchant, $split, $preview, $gatewayService);
        }

        try {
            if ($checkoutSession?->order_id) {
                $order = Order::query()->whereKey($checkoutSession->order_id)->with(['items', 'transaction', 'bnplItemLoans.installments'])->first();
            }

            if (!$order) {
                $order = $orderService->createExternalBnplOrder($user, $merchant, [
                    'amount' => $amount,
                    'currency' => data_get($payload, 'currency', setting('site_currency', 'global')),
                    'merchant_order_id' => data_get($payload, 'merchant_order_id'),
                    'merchant_reference_id' => data_get($payload, 'merchant_reference_id'),
                    'session_id' => $checkoutSession?->token,
                    'customer' => (array) data_get($payload, 'customer', []),
                    'items' => (array) data_get($payload, 'items', []),
                    'provider_platform' => (string) data_get($payload, 'metadata.platform', data_get($payload, 'platform', 'wordpress-woocommerce')),
                    'provider_source' => (string) data_get($payload, 'metadata.checkout_source', 'woocommerce_plugin'),
                ]);
            }

            if (!$order->bnplItemLoans()->exists()) {
                $orderService->processBnplOrder($order, $user, $split->id);
            }

            $order = $order->fresh(['items', 'transaction', 'transactions', 'bnplItemLoans.installments']);
            $orderService->orderPaymentSuccess($order, false);

            $order = $order->fresh(['items', 'transaction', 'transactions', 'bnplItemLoans.installments']);
            if ($order->status === OrderStatus::Pending->value) {
                $order->update(['status' => OrderStatus::Success->value]);
                $order->items()->update(['status' => OrderStatus::Success->value]);
            }

            $orderService->creditSellerOrderProceeds($order);

            if ($checkoutSession) {
                $checkoutSession->update([
                    'buyer_id' => $user->id,
                    'order_id' => $order->id,
                    'status' => 'confirmed',
                    'completed_at' => now(),
                ]);
            }
        } catch (Exception $exception) {
            if ($checkoutSession) {
                $checkoutSession->update([
                    'order_id' => $order?->id,
                    'buyer_id' => $user->id,
                    'status' => 'failed',
                ]);
            }

            if ($order) {
                $orderService->setOrderFailed($order->fresh(), false);
            }

            return back()->withErrors(['payment' => $exception->getMessage()])->withInput();
        }

        $this->notifyMerchantWebhook(
            data_get($payload, 'webhook_url', data_get($payload, 'callback_url')),
            $this->makeMerchantWebhookPayload($payload, $checkoutSession, $order, $gatewayService, $merchant)
        );

        $successUrl = data_get($payload, 'success_url');
        $redirectUrl = $successUrl
            ? $this->buildWooReturnUrl($successUrl, $payload, $checkoutSession, $merchant, 'approved', $gatewayService)
            : route('home');

        session()->forget(['bnpl.wp_payload', 'bnpl.checkout_session_token']);

        return redirect()->to($redirectUrl)->with('status', 'BNPL payment confirmed successfully.');
    }

    public function cancel()
    {
        $checkoutSession = $this->resolveCheckoutSession();
        $payload = $checkoutSession?->payload ?: session('bnpl.wp_payload');
        $cancelUrl = data_get($payload, 'cancel_url');
        session()->forget(['bnpl.wp_payload', 'bnpl.checkout_session_token']);

        if ($checkoutSession && $checkoutSession->status === 'processing') {
            $checkoutSession->update(['status' => 'pending']);
        } elseif ($checkoutSession && $checkoutSession->status === 'pending') {
            $checkoutSession->update(['status' => 'cancelled']);
        }

        if ($cancelUrl) {
            return redirect()->to($cancelUrl);
        }

        return redirect()->route('home');
    }

    private function notifyMerchantWebhook(?string $webhookUrl, array $payload): void
    {
        if (!$webhookUrl) {
            return;
        }

        try {
            Http::timeout(20)->asJson()->post($webhookUrl, $payload);
        } catch (Exception $exception) {
            Log::warning('BNPL callback failed', [
                'url' => $webhookUrl,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function appendQueryString(string $url, array $query): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($query);
    }

    private function resolveMerchant(array $payload): ?User
    {
        $publicKey = trim((string) data_get($payload, 'merchant_public_key'));

        if ($publicKey === '') {
            return null;
        }

        return User::query()
            ->where('public_key', $publicKey)
            ->where('user_type', 'merchant')
            ->first();
    }

    private function resolveCallbackSecret(User $merchant): string
    {
        return (string) ($merchant->webhook_secret ?: $merchant->secret_key ?: config('app.key'));
    }

    private function verifyCheckoutSignature(array $payload, User $merchant): bool
    {
        $signature = trim((string) data_get($payload, 'request_signature'));
        if ($signature === '') {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            implode('|', [
                (string) data_get($payload, 'merchant_public_key'),
                (string) data_get($payload, 'merchant_order_id'),
                number_format((float) data_get($payload, 'amount', 0), 2, '.', ''),
                (string) data_get($payload, 'currency', setting('site_currency', 'global')),
                (string) data_get($payload, 'timestamp'),
            ]),
            (string) $merchant->secret_key
        );

        return hash_equals($expected, $signature);
    }

    private function normalizeItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            return [
                'name' => (string) data_get($item, 'name', ''),
                'sku' => (string) data_get($item, 'sku', ''),
                'quantity' => (int) data_get($item, 'quantity', 1),
                'unit_price' => round((float) data_get($item, 'unit_price', 0), 2),
                'line_total' => round((float) data_get($item, 'line_total', 0), 2),
                'image' => (string) data_get($item, 'image', data_get($item, 'image_url', '')),
            ];
        })->values()->all();
    }

    private function extractPayload(Request $request): array
    {
        $payload = $request->all();

        if (isset($payload['customer']) && is_string($payload['customer'])) {
            $payload['customer'] = json_decode($payload['customer'], true) ?: [];
        }

        if (isset($payload['items']) && is_string($payload['items'])) {
            $payload['items'] = json_decode($payload['items'], true) ?: [];
        }

        if (isset($payload['metadata']) && is_string($payload['metadata'])) {
            $payload['metadata'] = json_decode($payload['metadata'], true) ?: [];
        }

        if (empty(data_get($payload, 'merchant_order_id')) || empty(data_get($payload, 'amount'))) {
            return [];
        }

        $merchant = $this->resolveMerchant($payload);
        if (!$merchant || !$this->verifyCheckoutSignature($payload, $merchant)) {
            return [];
        }

        return $payload;
    }

    private function resolveCheckoutSession(): ?BnplCheckoutSession
    {
        $token = session('bnpl.checkout_session_token');
        if (!$token) {
            return null;
        }

        $session = BnplCheckoutSession::query()->where('token', $token)->first();
        if ($session) {
            session(['bnpl.wp_payload' => $session->payload]);
        }

        return $session;
    }

    private function makeMerchantWebhookPayload(array $payload, ?BnplCheckoutSession $checkoutSession, ?Order $order, MerchantBnplGatewayService $gatewayService, User $merchant): array
    {
        $transactionId = (string) data_get($payload, 'merchant_order_id');
        $totalAmount = $gatewayService->formatAmount(data_get($payload, 'amount', 0));
        $sandboxOrderNumber = (string) data_get($checkoutSession?->sandbox_result, 'simulated_order_number', '');
        $sandboxTransactionReference = (string) data_get($checkoutSession?->sandbox_result, 'simulated_transaction_reference', '');

        return [
            'status' => 'success',
            'signature' => $gatewayService->makeWebhookSignature('success', $transactionId, $totalAmount, $merchant),
            'data' => [
                'transaction_id' => $transactionId,
                'total_amount' => $totalAmount,
                'currency' => (string) data_get($payload, 'currency', setting('site_currency', 'global')),
                'session_id' => $checkoutSession?->token,
                'sandbox_mode' => (bool) ($checkoutSession?->is_sandbox ?? false),
                'merchant_reference_id' => (string) data_get($payload, 'merchant_reference_id', ''),
                'xanzu_order_number' => $order ? (string) $order->order_number : $sandboxOrderNumber,
                'xanzu_transaction_reference' => $order ? (string) optional($order->transaction)->tnx : $sandboxTransactionReference,
            ],
        ];
    }

    private function buildWooReturnUrl(string $successUrl, array $payload, ?BnplCheckoutSession $checkoutSession, User $merchant, string $status, MerchantBnplGatewayService $gatewayService): string
    {
        $returnPayload = [
            'order_id' => (string) data_get($payload, 'merchant_order_id'),
            'status' => $status,
            'transaction_id' => (string) data_get($payload, 'merchant_order_id'),
            'session_id' => $checkoutSession?->token,
            'timestamp' => now()->toIso8601String(),
        ];
        $returnPayload['signature'] = $gatewayService->makeReturnSignature($returnPayload, $merchant);

        return $this->appendQueryString($successUrl, $returnPayload);
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

    private function checkoutStartFailedMessage(): string
    {
        return 'We could not start your BNPL checkout. Please return to the store and try again.';
    }

    private function invalidAmountMessage(): string
    {
        return 'We could not verify your order total for BNPL. Please return to the store and try again.';
    }

    private function siteCurrencyMessage(): string
    {
        return 'BNPL is only available for ' . (string) setting('site_currency', 'global') . ' orders on this store.';
    }

    private function bnplUnavailableMessage(): string
    {
        return 'BNPL is temporarily unavailable right now. Please try again later or choose another payment method.';
    }

    private function merchantUnavailableMessage(): string
    {
        return 'BNPL is temporarily unavailable for this store. Please choose another payment method.';
    }

    private function splitUnavailableMessage(): string
    {
        return 'That installment plan is no longer available. Please choose another plan.';
    }

    private function checkoutProcessingMessage(): string
    {
        return 'Your BNPL checkout is already being processed. Please wait a moment and try again.';
    }

    private function confirmSandboxSession(
        array $payload,
        BnplCheckoutSession $checkoutSession,
        User $merchant,
        CreditLimitSplit $split,
        array $preview,
        MerchantBnplGatewayService $gatewayService
    ) {
        $simulatedOrderNumber = 'SBOX-' . strtoupper(substr($checkoutSession->token, -8));
        $simulatedTransactionReference = 'SBTNX-' . strtoupper(substr($checkoutSession->token, -10));

        $checkoutSession->update([
            'buyer_id' => auth()->id(),
            'status' => 'confirmed',
            'completed_at' => now(),
            'sandbox_result' => [
                'split_id' => $split->id,
                'split_count' => (int) data_get($preview, 'split_count', 1),
                'initial_installment_deducted' => round((float) data_get($preview, 'initial_paid_amount', 0), 2),
                'remaining_financed_amount' => round((float) data_get($preview, 'final_amount_to_pay', 0), 2),
                'total_payable' => round((float) data_get($preview, 'total_payable', 0), 2),
                'simulated_order_number' => $simulatedOrderNumber,
                'simulated_transaction_reference' => $simulatedTransactionReference,
                'confirmed_at' => now()->toIso8601String(),
            ],
        ]);

        $this->notifyMerchantWebhook(
            data_get($payload, 'webhook_url', data_get($payload, 'callback_url')),
            $this->makeMerchantWebhookPayload($payload, $checkoutSession, null, $gatewayService, $merchant)
        );

        $successUrl = data_get($payload, 'success_url');
        $redirectUrl = $successUrl
            ? $this->buildWooReturnUrl($successUrl, $payload, $checkoutSession, $merchant, 'approved', $gatewayService)
            : route('home');

        session()->forget(['bnpl.wp_payload', 'bnpl.checkout_session_token']);

        return redirect()->to($redirectUrl)->with('status', 'Sandbox BNPL payment confirmed successfully.');
    }
}
