<?php

namespace App\Services\CardProvider\Stripe;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Models\Card;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditLimitService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class IpnService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function handle(Request $request)
    {
        $stripeCredential = plugin_active('Stripe Virtual Card');

        \Log::info('stripe webhook received', ['data' => $request->all()]);
        $payload = $request->getContent();
        $sig_header = $request->server('HTTP_STRIPE_SIGNATURE');
        $webhookSecret = json_decode($stripeCredential->data, true)['webhook_secret'] ?? null;

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhookSecret
            );

            // create txn based on the event type and data
            if ($event->type === 'issuing_transaction.created') {

                $transactionData = $event->data->object;

                $txnId = $transactionData->id ?? null;
                if (!$txnId) {
                    return;
                }

                if (Transaction::query()->where('tnx', $txnId)->exists()) {
                    return;
                }

                $rawAmount = (int) ($transactionData->amount ?? 0);
                if ($rawAmount === 0) {
                    return;
                }

                $amount = round(abs($rawAmount) / 100, 2);
                if ($amount <= 0) {
                    return;
                }

                $currency = strtoupper((string) ($transactionData->currency ?? setting('site_currency', 'global')));

                $metadata = json_decode(json_encode($transactionData->metadata), true) ?? [];
                $merchantData = json_decode(json_encode($transactionData->merchant_data), true) ?? [];

                $description = ($merchantData['name'] ?? 'Unknown Merchant')
                    . ' (' . ($merchantData['network_id'] ?? '-') . ') - '
                    . ($merchantData['category'] ?? '');

                // network txn id
                $networkTxnId = $transactionData->network_data ? ($transactionData->network_data->transaction_id) : null;

                $description .= $networkTxnId ? " [NTTxn: {$networkTxnId}]" : '';

                $userId = Card::query()->where('card_id', $transactionData->card)->value('user_id');
                if (!$userId) {
                    Log::warning('stripe card transaction without user', ['txn_id' => $txnId]);

                    return;
                }

                $user = User::query()->find($userId);
                if (!$user) {
                    Log::warning('stripe card transaction user missing', ['txn_id' => $txnId, 'user_id' => $userId]);

                    return;
                }

                if ($rawAmount < 0) {
                    $productName = trim((string) ($merchantData['name'] ?? 'Card Purchase'));
                    if (!empty($merchantData['category'])) {
                        $productName .= ' - ' . $merchantData['category'];
                    }

                    $orderService = app(OrderService::class);
                    $order = $orderService->createOutsideBnplOrderFromCard($user, [
                        'txn_id' => $txnId,
                        'amount' => $amount,
                        'currency' => $currency,
                        'product_name' => $productName,
                        'description' => $description,
                        'manual_field_data' => array_merge($metadata, $merchantData),
                        'method' => 'credit_card',
                    ]);

                    $splitId = $orderService->resolveDefaultSplitIdForUser($user);
                    if (!$splitId) {
                        throw new \Exception('No active BNPL split configuration found.');
                    }

                    $orderService->processBnplOrder($order, $user, $splitId, true);
                    $paidOrder = $orderService->orderPaymentSuccess(
                        $order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']),
                        false
                    );

                    if ($paidOrder) {
                        $orderService->setOrderDelivered($order->refresh()->load(['items.listing', 'transaction', 'bnplItemLoans.installments']));
                    }

                    return;
                }

                $transaction = new Transaction;
                $transaction->tnx = $txnId;
                $transaction->description = $description;
                $transaction->amount = $amount;
                $transaction->final_amount = $amount;
                $transaction->pay_currency = $currency;
                $transaction->status = TxnStatus::Success;
                $transaction->user_id = $user->id;
                $transaction->type = TxnType::Deposit;
                $transaction->manual_field_data = json_encode(array_merge($metadata, $merchantData));
                $transaction->method = 'credit_card';
                $transaction->pay_amount = $amount;
                $transaction->charge = 0;
                $transaction->save();

                app(CreditLimitService::class)->adjustUsed($user, -$amount);
            } elseif ($event->type === 'issuing_card.updated') {
                $cardData = $event->data->object;

                $cardId = $cardData->id ?? null;
                if (!$cardId) {
                    return;
                }

                $status = $cardData->status ?? null;
                if (!$status) {
                    return;
                }

                Card::query()->where('card_id', $cardId)->update([
                    'status' => $status == 'active' ? $status : 'inactive',
                ]);
            }
        } catch (\Throwable $th) {
            // throw $th;
            \Log::error('stripe webhook error', [
                'error' => $th->getMessage(),
                'data' => [
                    'payload' => $payload,
                    'sign_header' => $sig_header,
                    'webhook_sec' => $webhookSecret,
                ],
            ]);
        }
    }
}
