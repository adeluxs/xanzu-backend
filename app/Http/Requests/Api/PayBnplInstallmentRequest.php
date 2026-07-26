<?php

namespace App\Http\Requests\Api;

use App\Models\DepositMethod;
use Illuminate\Foundation\Http\FormRequest;

class PayBnplInstallmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_mode' => ['nullable', 'in:gateway,balance'],
            'gateway_code' => ['nullable', 'string', 'max:50'],
            'customFields' => ['nullable', 'array'],
            'gateway_fields' => ['nullable', 'array'],
            'manual_data' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $paymentMode = strtolower((string) $this->input('payment_mode', 'balance'));
            if ($paymentMode !== 'gateway') {
                return;
            }

            $gatewayCode = (string) $this->input('gateway_code', '');
            if ($gatewayCode === '') {
                $validator->errors()->add('gateway_code', __('A payment gateway is required.'));

                return;
            }

            $gateway = DepositMethod::where('gateway_code', $gatewayCode)->first();
            if (!$gateway) {
                $validator->errors()->add('gateway_code', __('Invalid payment gateway.'));

                return;
            }

            validateManualGatewayCustomFields($this, $validator, $gatewayCode, 'customFields');
        });
    }
}
