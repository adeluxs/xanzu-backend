<?php

namespace App\Http\Requests\Api\Merchant;

use App\Enums\ProviderPlatform;
use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $provider = Provider::where('user_id', optional($this->user())->id)->latest()->first();

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('providers', 'name')->ignore($provider?->id)],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'platform' => ['required', Rule::in(array_column(ProviderPlatform::cases(), 'value'))],
            'platform_host' => ['required', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:255'],
            'api_secret' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
