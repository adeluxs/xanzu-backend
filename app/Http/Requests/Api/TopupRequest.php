<?php

namespace App\Http\Requests\Api;

use App\Models\DepositMethod;
use Illuminate\Foundation\Http\FormRequest;

class TopupRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:1'],
            'gateway_code' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'amount.required' => __('A topup amount is required.'),
            'amount.min' => __('Topup amount must be at least 1.'),
            'gateway_code.required' => __('A payment method is required.'),
        ];
    }

    /**
     * Run additional validation after the basic rules pass.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $code = $this->input('gateway_code');

            $gateway = DepositMethod::where('gateway_code', $code)->first();

            if (! $gateway) {
                $validator->errors()->add('gateway_code', __('Invalid payment method.'));
            }
        });
    }
}
