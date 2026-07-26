<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiManagementController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $merchant = $request->user();

        if (!$merchant || $merchant->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        return $this->successResponse([
            'api_host' => url('/api/merchant'),
            'credentials' => [
                'public_key' => $merchant->public_key,
                'secret_key' => $merchant->secret_key,
                'webhook_secret' => $merchant->webhook_secret,
            ],
        ]);
    }

    public function generate(Request $request)
    {
        $merchant = $request->user();

        if (!$merchant || $merchant->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        [$publicKey, $secretKey, $webhookSecret] = $this->makeKeys();

        $merchant->update([
            'public_key' => $publicKey,
            'secret_key' => $secretKey,
            'webhook_secret' => $webhookSecret,
            'updated_at' => now(),
        ]);

        $merchant->refresh();

        return $this->successResponse([
            'api_host' => url('/api/merchant'),
            'credentials' => [
                'public_key' => $merchant->public_key,
                'secret_key' => $merchant->secret_key,
                'webhook_secret' => $merchant->webhook_secret,
            ],
        ], __('API credentials generated successfully.'));
    }

    private function makeKeys(): array
    {
        $publicKey = 'pk_' . Str::lower(Str::random(48));
        $secretKey = 'sk_' . Str::lower(Str::random(64));
        $webhookSecret = 'whsec_' . Str::lower(Str::random(64));

        return [$publicKey, $secretKey, $webhookSecret];
    }
}
