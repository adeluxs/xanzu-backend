<?php

namespace App\Services;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\DepositMethod;
use App\Models\Transaction;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use App\Traits\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AddMoneyService
{
    use ImageUpload, NotifyTrait, Payment;

    public function validate(array $data): bool
    {
        $rules = [
            'gateway' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];

        $gatewayInfo = DepositMethod::query()->whereKey($data['gateway'] ?? null)->where('status', 1)->first();
        if (! $gatewayInfo) {
            throw ValidationException::withMessages(['gateway' => __('Gateway does not exist or is inactive!')]);
        }

        $rules = array_merge($rules, $this->customFieldsValidation($gatewayInfo));
        Validator::make($data, $rules)->validate();

        return true;
    }

    private function customFieldsValidation(DepositMethod $data): array
    {
        $rules = [];
        foreach ($data->field_options ?? [] as $fieldKey => $field) {
            $validation = trim((string) ($field['validation'] ?? 'nullable')) ?: 'nullable';
            if (($field['type'] ?? null) === 'file') {
                $rules['customFields.'.$fieldKey] = $validation.'|file|mimes:jpeg,jpg,png,pdf|max:5120';
                continue;
            }
            $rules['customFields.'.$fieldKey] = $validation;
        }
        return $rules;
    }

    public function process($amount, $gateway, $customFields = null): mixed
    {
        $gatewayInfo = DepositMethod::query()->with('gateway')->whereKey($gateway)->where('status', 1)->first();
        if (! $gatewayInfo) {
            throw ValidationException::withMessages(['gateway' => __('Gateway does not exist or is inactive!')]);
        }

        $amount = round((float) $amount, 2);
        $rate = (float) $gatewayInfo->rate;
        $minimum = (float) $gatewayInfo->minimum_deposit;
        $maximum = (float) $gatewayInfo->maximum_deposit;

        if (! is_finite($rate) || $rate <= 0) {
            throw ValidationException::withMessages([
                'gateway' => __('This payment method is temporarily unavailable because its exchange rate is invalid.'),
            ]);
        }

        if ($minimum < 0 || ($maximum > 0 && $maximum < $minimum)) {
            throw ValidationException::withMessages([
                'gateway' => __('This payment method is temporarily unavailable because its deposit limits are invalid.'),
            ]);
        }

        if ($amount < $minimum || ($maximum > 0 && $amount > $maximum)) {
            $currencySymbol = setting('currency_symbol', 'global');
            throw ValidationException::withMessages(['amount' => __('Please deposit the amount within the range :min to :max', [
                'min' => $currencySymbol.formatCurrency($gatewayInfo->minimum_deposit),
                'max' => $currencySymbol.formatCurrency($gatewayInfo->maximum_deposit),
            ])]);
        }

        $user = auth()->user();
        if (! $user) {
            throw ValidationException::withMessages(['gateway' => __('Unauthorized.')]);
        }

        $charge = $gatewayInfo->charge_type === 'percentage'
            ? (($gatewayInfo->charge / 100) * $amount)
            : (float) $gatewayInfo->charge;
        $charge = round((float) $charge, 2);
        $finalAmount = round($amount + $charge, 2);
        // rate is the conversion from wallet/base amount to the gateway's pay currency.
        $payAmount = round($finalAmount * $rate, 8);
        $depositType = $gatewayInfo->type === 'auto' ? TxnType::Deposit : TxnType::ManualDeposit;
        $manualData = [];

        if ($customFields !== null && $gatewayInfo->type === 'manual') {
            foreach ($gatewayInfo->field_options ?? [] as $key => $value) {
                $customFieldValue = data_get($customFields, $key);
                if ($customFieldValue instanceof UploadedFile) {
                    $manualData[$value['name']] = self::imageUploadTrait(query: $customFieldValue, folder: 'deposit');
                } else {
                    $manualData[$value['name']] = data_get($customFields, $key, '');
                }
            }
        }

        /** @var Transaction $txnInfo */
        $txnInfo = DB::transaction(fn () => (new Txn)->new(
            $amount,
            $charge,
            $finalAmount,
            $gatewayInfo->gateway_code,
            'Deposit With '.$gatewayInfo->name,
            $depositType,
            TxnStatus::Pending,
            $gatewayInfo->currency,
            $payAmount,
            $user->id,
            null,
            'User',
            $manualData
        ));

        if ($gatewayInfo->type === 'manual') {
            $this->notifyManualDeposit($txnInfo, $gatewayInfo, $user);
            return $this->apiPayload($txnInfo, [
                'is_redirect' => false,
                'redirect_url' => null,
                'payment_status' => TxnStatus::Pending->value,
                'provider' => 'manual',
            ]);
        }

        try {
            $response = self::depositAutoGateway($gatewayInfo->gateway_code, $txnInfo);
        } catch (\Throwable $e) {
            // The transaction has already been persisted so the gateway can use
            // its reference. A gateway-init failure must therefore be recorded
            // explicitly instead of calling rollBack() after commit.
            $txnInfo->update(['status' => TxnStatus::Failed]);
            Log::error('Automatic deposit gateway initialization failed.', [
                'tnx' => $txnInfo->tnx,
                'gateway' => $gatewayInfo->gateway_code,
                'error' => $e->getMessage(),
            ]);
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        if (! needJsonResponse()) {
            if ($response instanceof RedirectResponse) {
                return $response;
            }
            if (is_array($response) && ! empty($response['redirect_url'])) {
                return redirect()->away($response['redirect_url']);
            }
            return $response;
        }

        return $this->apiPayload($txnInfo->fresh(), $this->normaliseGatewayResponse($response));
    }

    private function apiPayload(Transaction $transaction, array $gateway): array
    {
        return [
            'transaction' => $transaction,
            'gateway' => $gateway,
            'status_url' => url('/api/user/add-money/'.$transaction->tnx.'/status'),
        ];
    }

    private function normaliseGatewayResponse(mixed $response): array
    {
        if ($response instanceof RedirectResponse) {
            $target = $response->getTargetUrl();
            return [
                'is_redirect' => ! str($target)->contains(request()->getHost()),
                'redirect_url' => $target,
                'payment_status' => TxnStatus::Pending->value,
            ];
        }

        if (is_array($response)) {
            return [
                'is_redirect' => (bool) ($response['is_redirect'] ?? ! empty($response['redirect_url'])),
                'redirect_url' => $response['redirect_url'] ?? null,
                'token' => $response['token'] ?? null,
                'response_code' => $response['response_code'] ?? null,
                'payment_status' => $response['payment_status'] ?? TxnStatus::Pending->value,
                'provider' => $response['provider'] ?? null,
            ];
        }

        return [
            'is_redirect' => false,
            'redirect_url' => null,
            'payment_status' => TxnStatus::Pending->value,
        ];
    }

    private function notifyManualDeposit(Transaction $txnInfo, DepositMethod $gatewayInfo, $user): void
    {
        $shortcodes = [
            '[[amount]]' => $txnInfo->amount,
            '[[charge]]' => $txnInfo->charge,
            '[[currency]]' => setting('site_currency', 'global'),
            '[[gateway]]' => $gatewayInfo->name,
            '[[request_at]]' => date('d M, Y h:i A'),
            '[[total_amount]]' => $txnInfo->final_amount,
            '[[request_link]]' => route('admin.deposit.manual.pending'),
            '[[site_title]]' => setting('site_title', 'global'),
        ];

        try {
            $this->sendNotify(
                setting('support_email', 'global'),
                'manual_deposit_request',
                'Admin',
                $shortcodes,
                $user->phone,
                $user->id,
                route('admin.deposit.manual.pending')
            );
        } catch (\Throwable $e) {
            Log::warning('Manual deposit created but notification failed.', ['tnx' => $txnInfo->tnx, 'error' => $e->getMessage()]);
        }
    }
}
