<?php

namespace App\Http\Requests\Api;

use App\Enums\ListingType;
use App\Models\DepositMethod;
use App\Models\Listing;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    use ApiResponse;

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
            'payment_mode' => ['nullable', 'in:gateway,bnpl,balance'],
            'items' => ['bail', 'required', 'array', 'min:1'],
            'items.*.listing_id' => ['required', 'integer', 'exists:listings,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.selected_attributes' => ['nullable', 'array'],
            'items.*.selected_attributes.*' => ['exists:listing_attributes,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'save_shipping_address' => ['nullable', 'boolean'],
            'shipping_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_charge_type' => ['nullable', 'in:fixed,percentage'],
            'gateway_code' => ['nullable', 'string', 'max:50'],
            'split_id' => ['nullable', 'integer', 'exists:credit_limit_splits,id'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'items.required' => __('At least one item is required.'),
            'items.min' => __('At least one item is required.'),
            'items.*.listing_id.required' => __('Each item must have a listing ID.'),
            'items.*.listing_id.exists' => __('Product #:input does not exist.'),
            'items.*.quantity.required' => __('Quantity is required for every item.'),
            'items.*.quantity.min' => __('Quantity must be at least 1.'),
            'items.*.selected_attributes.*.exists' => __('One or more selected attributes are invalid.'),
            'gateway_code.required' => __('A payment method is required.'),
            'gateway_code.required_unless' => __('A payment method is required.'),
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

            $this->validateItems($validator);
            $this->validateGateway($validator);
            $this->validateBnpl($validator);
            // gateway custom field
            $this->validateGatewayCustomFields($validator);
        });
    }


    private function validateGatewayCustomFields($validator): void
    {
        validateManualGatewayCustomFields($this, $validator, (string) $this->input('gateway_code'));
    }



    /**
     * Validate each item for availability, stock, and ownership.
     */
    private function validateItems($validator): void
    {
        $hasPhysical = false;
        $hasDigital = false;

        foreach ($this->input('items', []) as $index => $item) {
            $listing = Listing::find($item['listing_id']);

            if (!$listing || $listing->status == 0) {
                $validator->errors()->add(
                    "items.{$index}.listing_id",
                    __('Product #:id is not available.', ['id' => $item['product_name']])
                );

                continue;
            }

            if ($listing->is_out_of_stock) {
                $validator->errors()->add(
                    "items.{$index}.listing_id",
                    __(':name is out of stock.', ['name' => $listing->product_name])
                );

                continue;
            }

            if ($listing->quantity < ($item['quantity'] ?? 1)) {
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    __('Requested quantity for :name is not available. Available: :qty.', [
                        'name' => $listing->product_name,
                        'qty' => $listing->quantity,
                    ])
                );
            }

            if ($listing->type === ListingType::PHYSICAL) {
                $hasPhysical = true;
            }

            if ($listing->type === ListingType::DIGITAL) {
                $hasDigital = true;
            }
        }

        if ($hasPhysical && $hasDigital) {
            $validator->errors()->add('items', __('Physical and digital products cannot be purchased in the same order.'));
        }
    }

    /**
     * Validate that the payment gateway exists (unless balance/topup).
     */
    private function validateGateway($validator): void
    {
        if ($this->isBnplMode()) {
            return;
        }

        $code = $this->input('gateway_code');

        if (in_array($code, ['balance'])) {
            return;
        }

        $gateway = DepositMethod::where('gateway_code', $code)->first();

        if (!$gateway) {
            $validator->errors()->add('gateway_code', __('Invalid payment method.'));
        }
    }

    private function validateBnpl($validator): void
    {
        if (!$this->isBnplMode()) {
            return;
        }

        $items = $this->input('items', []);
        if (count($items) !== 1) {
            $validator->errors()->add('items', __('BNPL allows only one item per order.'));

            return;
        }

        $qty = (int) ($items[0]['quantity'] ?? 1);
        if ($qty !== 1) {
            $validator->errors()->add('items.0.quantity', __('BNPL allows quantity 1 only.'));
        }
    }

    private function isBnplMode(): bool
    {
        return strtolower((string) $this->input('payment_mode', 'gateway')) === 'bnpl';
    }
}
