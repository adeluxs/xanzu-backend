<?php

namespace App\Services;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\DepositMethod;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use App\Traits\Payment;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AddMoneyService
{
    use ImageUpload, NotifyTrait, Payment;

    public function validate($data): ValidationException|bool
    {
        $rules = [
            'gateway' => 'required',
            'amount' => 'required|numeric|min:1',
        ];

        $gatewayInfo = DepositMethod::find($data['gateway'] ?? null);

        if (! $gatewayInfo) {
            throw ValidationException::withMessages(['gateway' => __('Gateway does not exist!')]);
        }

        $rules = \array_merge($rules, $this->customFieldsValidation($gatewayInfo));

        $validate = Validator::make($data, $rules)->validate();

        return true;
    }

    private function customFieldsValidation(DepositMethod $data)
    {
        $rules = [];

        foreach ($data->field_options ?? [] as $fieldKey => $field) {
            if ($field['type'] == 'file') {
                $rules['customFields.'.$fieldKey] = $field['validation'].'|mimes:jpeg,jpg,png,svg|max:2000';

                continue;
            }

            $rules['customFields.'.$fieldKey] = $field['validation'];
        }

        return $rules;
    }

    public function process($amount, $gateway, $customFields = null)
    {
        $gatewayInfo = DepositMethod::find($gateway) ?? null;

        if (! $gatewayInfo) {
            throw ValidationException::withMessages(['gateway' => __('Gateway does not exist!')]);
        }

        if ($amount < $gatewayInfo->minimum_deposit || $amount > $gatewayInfo->maximum_deposit) {
            $currencySymbol = setting('currency_symbol', 'global');
            $message = __('Please deposit the amount within the range :min to :max', [
                'min' => $currencySymbol.formatCurrency($gatewayInfo->minimum_deposit),
                'max' => $currencySymbol.formatCurrency($gatewayInfo->maximum_deposit),
            ]);

            throw ValidationException::withMessages(['amount' => $message]);
        }
        $user = auth()->user();

        try {

            $charge = $gatewayInfo->charge_type == 'percentage' ? ($gatewayInfo->charge / 100) * $amount : $gatewayInfo->charge;
            $finalAmount = $amount + $charge;
            $payAmount = $finalAmount;
            $depositType = $gatewayInfo->type == 'auto' ? TxnType::Deposit : TxnType::ManualDeposit;

            if ($customFields !== null && $gatewayInfo->type == 'manual') {
                $manualData = [];

                foreach ($gatewayInfo->field_options ?? [] as $key => $value) {
                    $customFieldValue = data_get($customFields, $key);
                    if ($customFieldValue instanceof UploadedFile) {
                        $manualData[$value['name']] = self::imageUploadTrait(query: $customFieldValue, folder: 'deposit');
                    } else {
                        $manualData[$value['name']] = data_get($customFields, $key, '');
                    }
                }

                $shortcodes = [
                    '[[amount]]' => $amount,
                    '[[charge]]' => $charge,
                    '[[currency]]' => setting('site_currency', 'global'),
                    '[[gateway]]' => $gatewayInfo->name,
                    '[[request_at]]' => date('d M, Y h:i A'),
                    '[[total_amount]]' => $finalAmount,
                    '[[request_link]]' => route('admin.deposit.manual.pending'),
                    '[[site_title]]' => setting('site_title', 'global'),
                ];

                $this->sendNotify(setting('support_email', 'global'), 'manual_deposit_request', 'Admin', $shortcodes, $user->phone, $user->id, route('admin.deposit.manual.pending'));
            }

            DB::beginTransaction();

            $txnInfo = (new Txn)->new($amount, $charge, $finalAmount, $gatewayInfo->gateway_code, 'Deposit With '.$gatewayInfo->name, $depositType, TxnStatus::Pending, $gatewayInfo->currency, $payAmount, $user->id, null, 'User', $manualData ?? []);

            DB::commit();

            $response = self::depositAutoGateway($gatewayInfo->gateway_code, $txnInfo);

            if (needJsonResponse()) {
                $hasRedirect = $response instanceof RedirectResponse && ! str($response->getTargetUrl())->contains(request()->getHost());
                $responseData = [
                    'transaction' => $txnInfo,
                    'gateway' => [
                        'is_redirect' => $hasRedirect,
                        'redirect_url' => $hasRedirect ? $response->getTargetUrl() : null,
                    ],
                ];

                return $responseData;
            }

            $response->is_redirect = false;

            return $response;
        } catch (\Throwable $throwable) {
            DB::rollBack();
            throw ValidationException::withMessages(['amount' => $throwable->getMessage()]);
        }
    }
}
