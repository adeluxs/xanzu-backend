<?php

namespace App\Http\Requests\Api\Merchant;

use App\Enums\ListingStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'provider_id' => ['nullable', 'exists:providers,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,amount'],
            'shipping_charge' => ['nullable', 'numeric', 'min:0'],
            'shipping_charge_type' => ['nullable', 'in:fixed,percentage'],
            'delivery_method' => ['required', 'in:manual,auto'],
            'delivery_speed' => ['nullable', 'integer', 'min:0'],
            'delivery_speed_unit' => ['nullable', 'in:second,minute,hour,day,week,month', 'required_with:delivery_speed'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'gallery' => ['nullable', 'array', 'max:4'],
            'gallery.*' => ['image', 'max:2048'],
            'status' => ['required', 'in:' . implode(',', array_column(ListingStatus::cases(), 'value'))],
            'is_flash' => ['nullable', 'boolean'],
            'has_attributes' => ['nullable', 'boolean'],
            'type' => ['required', 'in:digital,physical'],
        ];

        if ($this->hasAttributes()) {
            $rules['attribute_groups'] = ['required'];
            $rules['attribute_groups.*.group_name'] = ['required', 'string', 'max:255'];
            $rules['attribute_groups.*.attributes'] = ['required', 'array', 'min:1'];
            $rules['attribute_groups.*.attributes.*.label'] = ['required', 'string', 'max:255'];
            $rules['attribute_groups.*.attributes.*.price'] = ['required', 'numeric', 'min:0'];
            $rules['attribute_groups.*.attributes.*.discount_type'] = ['nullable', 'in:percentage,amount'];
            $rules['attribute_groups.*.attributes.*.discount_amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['attribute_groups.*.attributes.*.qty'] = ['required', 'integer', 'min:0'];
        } else {
            $rules['quantity'] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('is_flash') && ! $this->hasAttributes() && $this->float('discount_value', 0) <= 0) {
                $validator->errors()->add('is_flash', __('Flash sale is only available for discounted listings!'));
            }
        });
    }

    public function hasAttributes(): bool
    {
        return $this->input('type') === 'physical' && $this->boolean('has_attributes');
    }
}
