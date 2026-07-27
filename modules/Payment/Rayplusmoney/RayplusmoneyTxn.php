<?php

namespace Payment\Rayplusmoney;

use App\Enums\TxnStatus;
use Illuminate\Support\Facades\Http;
use Payment\Transaction\BaseTxn;
use Txn;

class RayplusmoneyTxn extends BaseTxn
{
    private string $baseUrl;

    private string $apiKey;

    private string $apiToken;

    public function __construct($txnInfo)
    {
        parent::__construct($txnInfo);

        $gatewayInfo = gateway_info('rayplusmoney');
        $this->baseUrl = rtrim((string) ($gatewayInfo->base_url ?? 'https://app.rayplusmoney.com/pay/v01'), '/');
        $this->apiKey = (string) ($gatewayInfo->api_key ?? '');
        $this->apiToken = (string) ($gatewayInfo->api_token ?? '');
    }

    public function deposit()
    {
        $payload = [
            'commande' => [
                'invoice' => [
                    'items' => [
                        [
                            'name' => $this->siteName . ' Deposit',
                            'description' => 'Wallet top-up',
                            'quantity' => 1,
                            'unit_price' => (int) round($this->amount),
                            'total_price' => (int) round($this->amount),
                        ],
                    ],
                    'total_amount' => (int) round($this->amount),
                    'devise' => $this->currency,
                    'description' => 'Wallet top-up for ' . $this->userName,
                    'customer' => (string) $this->userPhone,
                    'customer_firstname' => (string) $this->userName,
                    'customer_lastname' => '',
                    'customer_email' => (string) $this->userEmail,
                    'externalid' => $this->txn,
                ],
                'store' => [
                    'name' => $this->siteName,
                    'website_url' => url('/'),
                ],
                'actions' => [
                    'cancel_url' => route('status.cancel', ['reftrn' => encrypt($this->txn)]),
                    'returnurl' => route('status.success', ['reftrn' => encrypt($this->txn)]),
                    'callback_url' => route('ipn.rayplusmoney', ['reftrn' => encrypt($this->txn)]),
                    'callbackurl__method' => 'post_json',
                ],
                'custom_data' => [
                    'transaction_id' => $this->txn,
                    'ref' => $this->txn,
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Apikey' => $this->apiKey,
        ])->post($this->baseUrl . '/pay/v01/straight/checkout-invoice/create', $payload);

        $data = $response->json();

        if (isset($data['response_code']) && $data['response_code'] === '00') {
            Transaction::tnx($this->txn)->update([
                'approval_cause' => (string) ($data['token'] ?? ''),
            ]);
        }

        return [
            'is_redirect' => false,
            'redirect_url' => null,
            'token' => $data['token'] ?? null,
            'response_code' => $data['response_code'] ?? null,
            'response_text' => $data['response_text'] ?? null,
        ];
    }

    public function withdraw()
    {
        $payload = [
            'commande' => [
                'amount' => (int) round($this->amount),
                'top_up_wallet' => 0,
                'customer' => (string) $this->userPhone,
                'network' => '',
                'external_id' => $this->txn,
                'callback_url' => route('ipn.rayplusmoney', ['reftrn' => encrypt($this->txn)]),
                'callback_url_method' => 'post_json',
                'custom_data' => [
                    'transaction_id' => $this->txn,
                    'ref' => $this->txn,
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Apikey' => $this->apiKey,
        ])->post($this->baseUrl . '/pay/v01/straight/payout', $payload);

        $data = $response->json();

        if (isset($data['response_code']) && $data['response_code'] === '00') {
            Transaction::tnx($this->txn)->update([
                'approval_cause' => (string) ($data['token'] ?? ''),
                'status' => TxnStatus::Pending,
            ]);

            return true;
        }

        $user = \App\Models\User::find($this->userId);
        if ($user) {
            $user->increment('balance', $this->final_amount);
        }

        Transaction::tnx($this->txn)->update([
            'status' => TxnStatus::Failed,
        ]);

        return false;
    }
}
